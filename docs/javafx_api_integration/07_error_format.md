# Format des erreurs

## Structure

```json
{
  "success": false,
  "message": "Donnees invalides.",
  "errors": {
    "email": "Cette adresse email est deja utilisee."
  }
}
```

## Codes HTTP

| Code | Signification |
|---|---|
| 200 | Succes |
| 201 | Ressource creee |
| 400 | JSON invalide ou requete incorrecte |
| 401 | Token absent/invalide |
| 403 | Role insuffisant |
| 404 | Ressource introuvable |
| 422 | Erreur de validation ou regle metier |

## Exemple 401

```json
{
  "success": false,
  "message": "Authentification API requise."
}
```
