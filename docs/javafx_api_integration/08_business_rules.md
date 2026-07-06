# Regles metier

## Enfants

- Un parent ne peut gerer que ses propres enfants.
- `nom`, `prenom`, `dateNaissance`, `sexe` sont obligatoires.
- `dateNaissance` ne doit pas etre dans le futur.

## Reservations

- Un enfant ne peut pas reserver deux fois la meme activite.
- L'activite doit etre ouverte.
- L'activite doit etre future.
- L'activite ne doit pas etre complete.
- L'age de l'enfant doit etre entre `ageMin` et `ageMax`.
- La capacite maximale ne doit pas etre depassee.
- Une reservation terminee ne peut plus etre annulee.

Ces regles reutilisent `ReservationService`.

## Categories

- `nom` obligatoire.
- `nom` unique.
- Une categorie avec activites ne peut pas etre supprimee.

## Activites

- `titre`, `description`, `dateActivite`, horaires, capacite, ages et categorie obligatoires.
- `heureFin` doit etre superieure a `heureDebut`.
- `ageMax` doit etre superieur ou egal a `ageMin`.
- `capaciteMax` doit etre positive.
- `prix` doit etre positif ou nul.
- Statut valide : `OUVERTE`, `COMPLETE`, `ANNULEE`, `TERMINEE`.

## Utilisateurs

- Email unique.
- Password hashe.
- Admin peut creer parent ou admin.
- Register public API cree uniquement `ROLE_PARENT`.
- Un admin ne peut pas supprimer son propre compte.
- Le dernier admin ne peut pas etre supprime.
