# API walkthrough

Every request and response below was captured with `curl` against the API
running locally (`php artisan serve --port=8620`) on a database seeded with
`php artisan migrate:fresh --seed`. Tokens are real but belong to a throwaway
SQLite database; the token value is redacted so that nothing that looks like
a credential is committed.

## 1. Health check

### The service descriptor

```http
GET /
```

```http
HTTP/1.1 200 OK

{
  "service": "Time Capsule API",
  "status": "ok",
  "api": "/api/v1"
}
```

## 2. Register and receive a bearer token

### Create an account

```http
POST /api/v1/register

{
  "name": "Ada Lovelace",
  "email": "ada@example.com",
  "password": "correct-horse-battery-staple",
  "password_confirmation": "correct-horse-battery-staple"
}
```

```http
HTTP/1.1 200 OK

{
  "user": {
    "id": 2,
    "name": "Ada Lovelace",
    "email": "ada@example.com"
  },
  "token": "1|REDACTED-throwaway-sanctum-token"
}
```

## 3. Seal a capsule

### The note comes back masked, not in clear

```http
POST /api/v1/users/2/message-capsules

{
  "note": "Tomorrow you will be proud of how patient you were.",
  "scheduled_opening_time": "2026-10-25T04:32:58Z"
}
```

```http
HTTP/1.1 201 Created

{
  "data": {
    "id": 5,
    "note": "Tomo****",
    "scheduled_opening_time": "2026-10-25T04:32:58.000000Z",
    "is_opened": false,
    "can_be_opened": false
  }
}
```

## 4. Validation

### An opening time in the past is rejected

```http
POST /api/v1/users/2/message-capsules

{
  "note": "Too late.",
  "scheduled_opening_time": "2020-01-01T00:00:00Z"
}
```

```http
HTTP/1.1 422 Unprocessable Content

{
  "message": "The opening time must be in the future.",
  "errors": {
    "scheduled_opening_time": [
      "The opening time must be in the future."
    ]
  }
}
```

### A capsule cannot be created already open

```http
POST /api/v1/users/2/message-capsules

{
  "note": "Nice try.",
  "scheduled_opening_time": "2026-10-25T04:32:58Z",
  "is_opened": true
}
```

```http
HTTP/1.1 422 Unprocessable Content

{
  "message": "A capsule cannot be created already open.",
  "errors": {
    "is_opened": [
      "A capsule cannot be created already open."
    ]
  }
}
```

## 5. Listing

### Every note still sealed is masked by the server

```http
GET /api/v1/users/2/message-capsules
```

```http
HTTP/1.1 200 OK

{
  "data": [
    {
      "id": 5,
      "note": "Tomo****",
      "scheduled_opening_time": "2026-10-25T04:32:58.000000Z",
      "is_opened": false,
      "can_be_opened": false
    }
  ]
}
```

### Pagination is opt-in with ?per_page=

```http
GET /api/v1/users/2/message-capsules?per_page=1
```

```http
HTTP/1.1 200 OK

{
  "data": [
    {
      "id": 5,
      "note": "Tomo****",
      "scheduled_opening_time": "2026-10-25T04:32:58.000000Z",
      "is_opened": false,
      "can_be_opened": false
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8620/api/v1/users/2/message-capsules?page=1",
    "last": "http://127.0.0.1:8620/api/v1/users/2/message-capsules?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://127.0.0.1:8620/api/v1/users/2/message-capsules?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "path": "http://127.0.0.1:8620/api/v1/users/2/message-capsules",
    "per_page": 1,
    "to": 1,
    "total": 1
  }
}
```

## 6. Opening

### Opening early is 403, not 401 — the caller is logged in, just early

```http
PUT /api/v1/users/2/message-capsules/5/open
```

```http
HTTP/1.1 403 Forbidden

{
  "message": "Message capsule cannot be opened yet - time remaining."
}
```

### The seeded demo account, whose capsule 2 has already unlocked

```http
PUT /api/v1/users/1/message-capsules/2/open
```

```http
HTTP/1.1 200 OK

{
  "data": {
    "id": 2,
    "note": "Remember to call your sister on her birthday.",
    "scheduled_opening_time": "2026-09-25T02:32:53.000000Z",
    "is_opened": true,
    "can_be_opened": false
  }
}
```

## 7. Authorisation

### One account cannot read another account's collection

```http
GET /api/v1/users/1/message-capsules
```

```http
HTTP/1.1 403 Forbidden

{
  "message": "You are not authorized to act on behalf of this user."
}
```

### An anonymous request is 401 — including one that did not ask for JSON

```http
GET /api/v1/users/2/message-capsules
```

```http
HTTP/1.1 401 Unauthorized

{
  "message": "Unauthenticated."
}
```

## 8. Log out

### 204, not a redirect

```http
POST /api/v1/logout
```

```http
HTTP/1.1 204 No Content

```

