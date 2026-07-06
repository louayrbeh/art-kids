# ArtKids API REST pour JavaFX

Ce dossier documente la couche API REST ajoutee au projet Symfony ArtKids.

Symfony reste le backend principal et conserve MySQL comme source de verite. L'application JavaFX doit devenir un client desktop qui consomme cette API via HTTP JSON. JavaFX ne doit pas se connecter directement a MySQL.

## Fichiers

- `01_api_overview.md` : vue generale de l'API.
- `02_authentication.md` : login, token Bearer et `/api/me`.
- `03_roles_and_permissions.md` : roles et protections.
- `04_endpoints_parent.md` : endpoints parent.
- `05_endpoints_admin.md` : endpoints admin.
- `06_json_models.md` : structures JSON.
- `07_error_format.md` : format des erreurs.
- `08_business_rules.md` : regles metier a respecter.
- `09_javafx_integration_plan.md` : plan d'integration JavaFX.
- `10_javafx_prompt_for_integration.md` : prompt copiable dans le repository JavaFX.
- `api_examples.md` : exemples curl.

## Important

L'application web Symfony existante continue de fonctionner avec ses routes Twig, formulaires, uploads, reservations et IA. L'API est ajoutee en parallele sous le prefixe `/api`.
