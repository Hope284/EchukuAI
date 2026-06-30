# Model Display Name vs Runtime Mapping

Each selectable model has two identities: a public DZEVA name and an internal provider/model mapping. Views and user-facing API payloads receive only the public label, capability, icon, and description. Provider adapters receive only the configured runtime identifier.

A branding change must never overwrite the backend identifier sent to a provider.
