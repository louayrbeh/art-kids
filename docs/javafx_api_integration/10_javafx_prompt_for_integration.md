# Prompt Codex pour integrer JavaFX avec l'API Symfony

Tu travailles dans le repository JavaFX ArtKids.

Objectif : remplacer progressivement les donnees mockees par des appels HTTP vers l'API REST Symfony documentee dans `docs/javafx_api_integration/`.

Regles :

- Symfony API est la source de verite.
- JavaFX ne doit pas se connecter directement a MySQL.
- Garde l'interface JavaFX existante.
- Garde la separation Front-Office Parent / Back-Office Admin.
- Utilise Java 17+, JavaFX, Maven et `java.net.http.HttpClient`.
- Lis toute la documentation dans `docs/javafx_api_integration/`.

Travail a faire :

1. Creer ou completer `ApiClient` avec :
   - `baseUrl`;
   - methodes `get`, `post`, `put`, `delete`;
   - serialisation JSON;
   - ajout automatique du header `Authorization: Bearer TOKEN`.
2. Remplacer `AuthService` mock par `AuthApiService`.
3. Implementer `POST /api/login`.
4. Stocker le token Bearer en memoire dans une session JavaFX.
5. Implementer `GET /api/me`.
6. Remplacer `ChildService` mock par `ChildApiService`.
7. Remplacer `CategoryService` mock par `CategoryApiService`.
8. Remplacer `ActivityService` mock par `ActivityApiService`.
9. Remplacer `ReservationService` mock par `ReservationApiService`.
10. Creer les services admin necessaires :
    - `AdminCategoryApiService`;
    - `AdminActivityApiService`;
    - `AdminUserApiService`;
    - `AdminChildApiService`;
    - `AdminReservationApiService`.
11. Mapper les JSON vers les modeles Java :
    - `User`;
    - `Child`;
    - `Category`;
    - `Activity`;
    - `Reservation`.
12. Afficher les erreurs API avec `AlertUtils`.
13. Ajouter des loading states simples pendant les appels.
14. Tester :
    - login admin;
    - login parent;
    - liste enfants parent;
    - creation enfant;
    - liste activites;
    - reservation;
    - annulation reservation;
    - categories admin;
    - activites admin;
    - users admin;
    - reservations admin.

Ne supprime pas le design JavaFX. Ne reviens pas a MySQL direct. Les mocks peuvent rester en fallback uniquement si c'est documente clairement.
