# Vue generale API

## Base URL

En local :

```text
http://127.0.0.1:8000
```

Toutes les routes API commencent par :

```text
/api
```

## Format JSON

Toutes les requetes de creation/modification utilisent :

```http
Content-Type: application/json
Accept: application/json
```

## Reponse succes

```json
{
  "success": true,
  "data": {}
}
```

## Reponse erreur

```json
{
  "success": false,
  "message": "Message clair",
  "errors": {
    "field": "Erreur"
  }
}
```

## Authentification

L'API utilise un token Bearer stateless signe par Symfony avec `APP_SECRET`.

```http
Authorization: Bearer TOKEN
```

Le bundle LexikJWT n'est pas requis pour cette premiere integration. Aucun changement de base de donnees n'est necessaire.
