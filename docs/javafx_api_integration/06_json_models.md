# Modeles JSON

## User

```json
{
  "id": 1,
  "nom": "Dupont",
  "prenom": "Marie",
  "fullName": "Marie Dupont",
  "email": "parent@artkids.test",
  "telephone": "0607080910",
  "roles": ["ROLE_PARENT"],
  "isActive": true,
  "createdAt": "2026-07-06T10:00:00+00:00",
  "updatedAt": null
}
```

## Child

```json
{
  "id": 1,
  "nom": "Dupont",
  "prenom": "Lina",
  "fullName": "Lina Dupont",
  "dateNaissance": "2018-05-10",
  "age": 8,
  "sexe": "FILLE",
  "parent": {}
}
```

`parent` est present seulement sur certains endpoints admin.

## Category

```json
{
  "id": 1,
  "nom": "Peinture",
  "description": "Ateliers peinture",
  "image": "image.jpg",
  "imageUrl": "/uploads/categories/image.jpg"
}
```

## Activity

```json
{
  "id": 1,
  "titre": "Atelier dessin",
  "description": "...",
  "image": "image.jpg",
  "imageUrl": "/uploads/activities/image.jpg",
  "dateActivite": "2026-07-20",
  "heureDebut": "10:00",
  "heureFin": "12:00",
  "capaciteMax": 15,
  "placesDisponibles": 8,
  "ageMin": 6,
  "ageMax": 12,
  "prix": "25.00",
  "statut": "OUVERTE",
  "lieu": "Salle 1",
  "category": {
    "id": 2,
    "nom": "Dessin"
  }
}
```

## Reservation

```json
{
  "id": 1,
  "dateReservation": "2026-07-06T10:00:00+00:00",
  "statut": "EN_ATTENTE",
  "child": {},
  "activity": {}
}
```
