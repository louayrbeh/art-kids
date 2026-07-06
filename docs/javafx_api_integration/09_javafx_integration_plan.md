# Plan integration JavaFX

1. Creer un `ApiClient` Java base sur `java.net.http.HttpClient`.
2. Ajouter `baseUrl`, par exemple `http://127.0.0.1:8000`.
3. Stocker le token apres `POST /api/login`.
4. Ajouter automatiquement `Authorization: Bearer TOKEN` aux requetes protegees.
5. Remplacer progressivement les services mockes :
   - `AuthService` -> `AuthApiService`
   - `ChildService` -> `ChildApiService`
   - `CategoryService` -> `CategoryApiService`
   - `ActivityService` -> `ActivityApiService`
   - `ReservationService` -> `ReservationApiService`
   - services admin -> `AdminApiService` ou services specialises.
6. Mapper les JSON vers les modeles Java existants.
7. Gerer les erreurs API avec alertes JavaFX propres.
8. Ajouter des etats loading/disabled pendant les appels HTTP.
9. Tester login parent/admin.
10. Tester CRUD parent puis CRUD admin.

JavaFX ne doit pas se connecter directement a MySQL.
