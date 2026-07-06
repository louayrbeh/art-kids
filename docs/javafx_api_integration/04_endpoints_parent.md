# Endpoints Parent

## Enfants

| Methode | URL | Description |
|---|---|---|
| GET | `/api/parent/children` | Liste les enfants du parent connecte |
| POST | `/api/parent/children` | Cree un enfant |
| GET | `/api/parent/children/{id}` | Detail d'un enfant du parent |
| PUT | `/api/parent/children/{id}` | Modifie un enfant du parent |
| DELETE | `/api/parent/children/{id}` | Supprime un enfant du parent |

Body create/update :

```json
{
  "nom": "Ben Ali",
  "prenom": "Youssef",
  "dateNaissance": "2018-05-10",
  "sexe": "GARCON"
}
```

## Activites

| Methode | URL | Description |
|---|---|---|
| GET | `/api/parent/activities` | Liste les activites ouvertes, futures et disponibles |
| GET | `/api/parent/activities/{id}` | Detail d'une activite |

## Reservations

| Methode | URL | Description |
|---|---|---|
| GET | `/api/parent/reservations` | Liste les reservations du parent |
| POST | `/api/parent/reservations` | Cree une reservation |
| PUT | `/api/parent/reservations/{id}/cancel` | Annule une reservation du parent |

Body creation :

```json
{
  "childId": 1,
  "activityId": 5
}
```

Erreurs possibles :

- enfant n'appartient pas au parent ;
- activite introuvable ;
- enfant deja inscrit ;
- activite complete ;
- activite annulee/passee ;
- age incompatible.
