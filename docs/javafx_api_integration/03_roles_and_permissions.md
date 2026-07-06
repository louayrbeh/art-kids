# Roles et permissions

## Roles

- `ROLE_PARENT` : espace parent.
- `ROLE_ADMIN` : back-office admin.

## Routes publiques

- `POST /api/login`
- `POST /api/register`

## Routes parent

Toutes les routes sous `/api/parent` exigent `ROLE_PARENT`.

Un parent ne peut voir, modifier ou supprimer que ses propres enfants et reservations.

## Routes admin

Toutes les routes sous `/api/admin` exigent `ROLE_ADMIN`.

Un parent qui appelle une route admin recoit `403 Forbidden`.

## Web Symfony

Les firewalls et routes web existants restent actifs. Le login Twig et les dashboards web ne sont pas remplaces par l'API.
