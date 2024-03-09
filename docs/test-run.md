# Captured terminal output

Real output from the commands named, on PHP 8.3.33 with an in-memory SQLite
database. Nothing here is transcribed by hand.

## `composer test` — the full suite

```
PHPUnit 10.5.11 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.33
Configuration: ./phpunit.xml

    .    .    .    .....    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .    .                      44 / 44 (100%)

Time: 00:00.807, Memory: 42.50 MB

Authentication (Tests\Feature\Authentication)
 ✔ Registration returns the user and a usable token
 ✔ Registration requires a matching password confirmation
 ✔ Login returns a token
 ✔ Login with the wrong password is rejected
 ✔ Logging in while already signed in answers in json
 ✔ Logout answers the api instead of crashing
 ✔ The user payload never leaks credentials

Health Check (Tests\Feature\HealthCheck)
 ✔ The root route describes the service

Message Capsule (Tests\Feature\MessageCapsule)
 ✔ It lists the owners capsules ordered by opening time
 ✔ The listing returns the full collection by default
 ✔ Pagination is opt in
 ✔ A page size beyond the cap is rejected
 ✔ It seals a new capsule
 ✔ The create response is masked and carries no internal columns
 ✔ It stores an iso 8601 instant as a real timestamp
 ✔ An offset timestamp is normalised to utc
 ✔ It rejects an empty note
 ✔ It rejects an opening time in the past
 ✔ A capsule cannot be created already open
 ✔ A note longer than the configured maximum is rejected
 ✔ A sealed note is masked by the server
 ✔ An unlocked but unopened note is still masked in the listing
 ✔ An opened note is returned in full
 ✔ It returns a single capsule once its time has passed
 ✔ Reading a still sealed capsule is forbidden not unauthenticated
 ✔ An unknown capsule is a 404
 ✔ It opens a capsule whose time has passed and reveals the note
 ✔ Opening is idempotent
 ✔ Opening early is forbidden and leaves the capsule sealed
 ✔ The unlock boundary is inclusive
 ✔ An anonymous request is rejected with 401
 ✔ An anonymous non json request is a 401 not a 500
 ✔ A user cannot read another users collection
 ✔ A user cannot create a capsule for another user
 ✔ A user cannot open another users capsule
 ✔ A user key in the payload does not crash the ownership check

Message Capsule (Tests\Unit\MessageCapsule)
 ✔ A sealed capsule cannot be opened before its time
 ✔ The boundary instant itself unlocks the capsule
 ✔ An already opened capsule cannot be opened again
 ✔ An offset carrying timestamp is understood as an absolute instant

Prefix Note Masker (Tests\Unit\PrefixNoteMasker)
 ✔ It keeps a four character teaser
 ✔ It masks a note no longer than the preview entirely
 ✔ It counts characters not bytes
 ✔ The preview length is configurable

OK (44 tests, 127 assertions)
```

## `composer lint:check` — Pint, Laravel preset

```
  ............................................................................
  ..

  ──────────────────────────────────────────────────────────────────── Laravel
    PASS   .......................................................... 78 files
```

## `php artisan route:list --except-vendor` — the whole API surface

```
  GET|HEAD  / ......................................................... health
  GET|HEAD  api/v1/user ............................................. api.user
  GET|HEAD  api/v1/users/{user}/message-capsules users.message-capsules.index…
  POST      api/v1/users/{user}/message-capsules users.message-capsules.store…
  GET|HEAD  api/v1/users/{user}/message-capsules/{message_capsule} users.mess…
  PUT       api/v1/users/{user}/message-capsules/{message_capsule}/open users…

                                                            Showing [6] routes
```
