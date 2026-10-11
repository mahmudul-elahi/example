---
paths:
  - 'app/Enums/**'
---

# Enums

## Use backed enums for closed value sets
Closed sets of domain values are `string`-backed enums, never free strings or database rows that can grow unchecked.

## The Roles enum is the single source of truth
Roles are defined exclusively in `app/Enums/Roles.php` and MUST NOT be extended: only `admin` and `user` exist. Every user holds exactly one role (see `.ai/rules/models.md`).

## Example

`app/Enums/Roles.php`

```php
enum Roles: string
{
    case Admin = 'admin';
    case User = 'user';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

Use the enum when persisting, seeding, and validating role values:

```php
Role::findOrCreate(Roles::Admin->value);
'role' => ['required', 'string', Rule::in(Roles::values())],
$user->setRole(Roles::User);
```

Never invent a third role value; add cases here only with user approval.