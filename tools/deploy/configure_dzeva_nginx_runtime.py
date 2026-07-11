#!/usr/bin/env python3
"""Apply DZEVA runtime nginx settings idempotently.

This keeps upload size and Phone Call Agent ConversationRelay WebSocket proxy
requirements in the deploy path without relying on fragile shell heredocs.
"""

from __future__ import annotations

import re
import sys
from pathlib import Path


def server_blocks(source: str) -> list[tuple[int, int, str]]:
    blocks: list[tuple[int, int, str]] = []

    for match in re.finditer(r"(?m)^\s*server\s*\{", source):
        depth = 0
        start = match.start()

        for index in range(match.end() - 1, len(source)):
            char = source[index]
            if char == "{":
                depth += 1
            elif char == "}":
                depth -= 1
                if depth == 0:
                    blocks.append((start, index + 1, source[start : index + 1]))
                    break

    return blocks


def main() -> int:
    if len(sys.argv) != 2:
        print("Usage: configure_dzeva_nginx_runtime.py <nginx-site-conf>", file=sys.stderr)
        return 2

    path = Path(sys.argv[1])
    text = path.read_text()
    blocks = [block for block in server_blocks(text) if "dzeva.com" in block[2]]

    if not blocks:
        print("No nginx server block contains dzeva.com", file=sys.stderr)
        return 1

    target = next((block for block in blocks if "listen 443" in block[2] or "ssl" in block[2]), blocks[0])
    start, end, block = target
    insertions: list[str] = []

    if "client_max_body_size" not in block:
        insertions.append("    client_max_body_size 100M;\n")

    if "location /api/phone-call-agent/ws/twilio/" not in block:
        insertions.append(
            "\n"
            "    location /api/phone-call-agent/ws/twilio/ {\n"
            "        proxy_pass http://127.0.0.1:8090;\n"
            "        proxy_http_version 1.1;\n"
            "        proxy_set_header Upgrade $http_upgrade;\n"
            "        proxy_set_header Connection \"Upgrade\";\n"
            "        proxy_set_header Host $host;\n"
            "        proxy_read_timeout 3600s;\n"
            "        proxy_send_timeout 3600s;\n"
            "        proxy_buffering off;\n"
            "    }\n"
        )

    if insertions:
        replacement = block[:-1] + "\n" + "".join(insertions) + "}"
        path.write_text(text[:start] + replacement + text[end:])

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
