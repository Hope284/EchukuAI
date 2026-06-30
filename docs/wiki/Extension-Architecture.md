# Extension Architecture

Extensions live under `app/Extensions`, register service providers, routes, migrations, views, commands, and publishable assets. An installed extension is usable only when its manifest is valid, provider is registered, migrations and assets are present, menu authorization passes, and the current plan grants access.

Do not force a menu item visible when the underlying extension is missing or disabled.
