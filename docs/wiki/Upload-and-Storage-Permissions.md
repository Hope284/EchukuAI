# Upload and Storage Permissions

Profile and blog images are stored below public upload directories using random names and validated MIME/extension/size rules. SVG files are sanitized. Replacements delete only files proven to belong to the current resource owner.

Production directories use setgid group-writable permissions (`2775`) and files use `664`; never use `777`. Deployment preserves both legacy `public/upload` and current `public/uploads` content.
