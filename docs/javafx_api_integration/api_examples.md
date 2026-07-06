# Exemples curl

Remplacer `TOKEN` par le token retourne par `/api/login`.

## Login parent

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"parent@artkids.test\",\"password\":\"parent123\"}"
```

## Login admin

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@artkids.test\",\"password\":\"admin123\"}"
```

## Get me

```bash
curl http://127.0.0.1:8000/api/me \
  -H "Authorization: Bearer TOKEN"
```

## Get parent children

```bash
curl http://127.0.0.1:8000/api/parent/children \
  -H "Authorization: Bearer TOKEN"
```

## Create child

```bash
curl -X POST http://127.0.0.1:8000/api/parent/children \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"nom\":\"Ben Ali\",\"prenom\":\"Youssef\",\"dateNaissance\":\"2018-05-10\",\"sexe\":\"GARCON\"}"
```

## Get activities

```bash
curl http://127.0.0.1:8000/api/parent/activities \
  -H "Authorization: Bearer TOKEN"
```

## Create reservation

```bash
curl -X POST http://127.0.0.1:8000/api/parent/reservations \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"childId\":1,\"activityId\":5}"
```

## Cancel reservation

```bash
curl -X PUT http://127.0.0.1:8000/api/parent/reservations/1/cancel \
  -H "Authorization: Bearer TOKEN"
```

## Admin list categories

```bash
curl http://127.0.0.1:8000/api/admin/categories \
  -H "Authorization: Bearer TOKEN"
```

## Admin create activity

```bash
curl -X POST http://127.0.0.1:8000/api/admin/activities \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"titre\":\"Atelier peinture\",\"description\":\"Description\",\"categoryId\":1,\"dateActivite\":\"2026-07-20\",\"heureDebut\":\"10:00\",\"heureFin\":\"12:00\",\"capaciteMax\":15,\"ageMin\":6,\"ageMax\":12,\"prix\":\"25.00\",\"statut\":\"OUVERTE\",\"lieu\":\"Salle 1\"}"
```

## Admin confirm reservation

```bash
curl -X PUT http://127.0.0.1:8000/api/admin/reservations/1/confirm \
  -H "Authorization: Bearer TOKEN"
```
