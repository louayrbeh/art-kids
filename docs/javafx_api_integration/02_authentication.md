# Authentification

## Login

```http
POST /api/login
```

Body :

```json
{
  "email": "parent@artkids.test",
  "password": "parent123"
}
```

Reponse :

```json
{
  "success": true,
  "data": {
    "token": "...",
    "tokenType": "Bearer",
    "expiresIn": 86400,
    "user": {
      "id": 1,
      "nom": "Dupont",
      "prenom": "Marie",
      "email": "parent@artkids.test",
      "roles": ["ROLE_PARENT"]
    }
  }
}
```

## Utilisateur connecte

```http
GET /api/me
Authorization: Bearer TOKEN
```

Retourne le modele JSON `User`.

## Logout

```http
POST /api/logout
Authorization: Bearer TOKEN
```

Le token etant stateless, JavaFX doit simplement supprimer le token stocke localement.

## Expiration

Le token expire apres `86400` secondes par defaut.
