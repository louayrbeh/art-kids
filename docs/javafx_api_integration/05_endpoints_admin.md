# Endpoints Admin

## Categories

| Methode | URL |
|---|---|
| GET | `/api/admin/categories` |
| POST | `/api/admin/categories` |
| GET | `/api/admin/categories/{id}` |
| PUT | `/api/admin/categories/{id}` |
| DELETE | `/api/admin/categories/{id}` |

Body :

```json
{
  "nom": "Peinture",
  "description": "Activites de peinture",
  "image": "image.jpg"
}
```

## Activites

| Methode | URL |
|---|---|
| GET | `/api/admin/activities` |
| POST | `/api/admin/activities` |
| GET | `/api/admin/activities/{id}` |
| PUT | `/api/admin/activities/{id}` |
| DELETE | `/api/admin/activities/{id}` |

Body :

```json
{
  "titre": "Atelier peinture",
  "description": "Description",
  "categoryId": 1,
  "dateActivite": "2026-07-20",
  "heureDebut": "10:00",
  "heureFin": "12:00",
  "capaciteMax": 15,
  "ageMin": 6,
  "ageMax": 12,
  "prix": "25.00",
  "statut": "OUVERTE",
  "lieu": "Salle 1",
  "image": "image.jpg"
}
```

## Utilisateurs

| Methode | URL |
|---|---|
| GET | `/api/admin/users` |
| POST | `/api/admin/users` |
| GET | `/api/admin/users/{id}` |
| PUT | `/api/admin/users/{id}` |
| DELETE | `/api/admin/users/{id}` |

Body :

```json
{
  "nom": "Admin",
  "prenom": "Test",
  "email": "test@example.com",
  "telephone": "12345678",
  "password": "password123",
  "roles": ["ROLE_PARENT"],
  "isActive": true
}
```

## Enfants

| Methode | URL |
|---|---|
| GET | `/api/admin/children` |
| GET | `/api/admin/children/{id}` |

## Reservations

| Methode | URL |
|---|---|
| GET | `/api/admin/reservations` |
| GET | `/api/admin/reservations/{id}` |
| PUT | `/api/admin/reservations/{id}/confirm` |
| PUT | `/api/admin/reservations/{id}/cancel` |
| PUT | `/api/admin/reservations/{id}/finish` |
