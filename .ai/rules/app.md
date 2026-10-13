---
paths:
  - 'app/**'
---

# App

## Store and serve files on the default disk
Never hardcode a storage disk (for example `Storage::disk('public')` or `->store('path', 'public')`). Use the default disk so it follows `FILESYSTEM_DISK` and storage can move to S3 with an env change only. Call `Storage::url()`, `Storage::put()`, `Storage::delete()`, `$file->store('path')` without a disk argument.
