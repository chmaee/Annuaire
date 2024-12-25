# Annuaire en Ligne - Projet Symfony G4 2024/2025

## Description

Annuaire en ligne permettant aux utilisateurs de créer et de gérer leur profil personnel. Chaque utilisateur peut compléter son profil avec diverses informations et contrôler sa visibilité sur le site.

### Fonctionnalités principales :

- **Création et modification :**
  Un utilisateur peut s'inscrire avec un login, un mot de passe, une adresse mail et un code unique.
  Il doit également choisir si son profil est visible ou non. Le choix du code unique est aidé par une vérification en temps réel utilisant une requête asynchrone.
  Après la création, l'utilisateur peut modifier son code et ses informations. Il peut également ajouter un numéro de téléphone.
    - Créez un nouvel utilisateur de façon interactive ou directement avec les commandes :
    ```bash
        php bin/console app:create-user
        php bin/console app:create-user "Bruno6" "Motdepasse6" "email@exemple.com" --role="ROLE_ADMIN" --code="Code6" --visible=true --telephone="0123456789"
     ```

- **Administration :**
  L'administrateur a accès aux profils masqués et a la possibilité de modifier et supprimer les comptes non-administrateurs. Le super administrateur dispose des mêmes droits sur les administrateurs.
    - Donnez ou retirez les droits d'administrateur avec les commandes :
    ```bash
        php bin/console app:donner-admin User
        php bin/console app:retirer-admin User
     ```
    - Donnez ou retirez les droits de super administrateur avec les commandes :
    ```bash
        php bin/console app:donner-super-admin User
        php bin/console app:retirer-super-admin User
     ```

- **Divers :**
    - **Dates de dernière édition et connexion** affichées sur le profil.
    - **Mode maintenance** : activation via un paramètre maintenance_mode dans `services.yaml`, redirigeant toutes les pages vers un message de maintenance.
    - **Export JSON** : obtention des informations d'un profil au format JSON via une route dédiée.


## Technologies Utilisées

- **Framework :** Symfony
- **Langages :** PHP, JavaScript
- **Base de données :** MySQL
- **Moteur de template :** Twig
- **Versionnage :** GitLab

## Installation

1. **Cloner le dépôt :**

   ```bash
   git clone git@gitlabinfo.iutmontp.univ-montp2.fr:web-but3/annuaire.git
   ```

2. **Installer les dépendances :**

   ```bash
   composer install
   ```
3. **Créer la base de données :**

   ```bash
   php bin/console doctrine:database:create
   ```

## Liste des principales routes

| Name                     | Method    | Path                                  |
|--------------------------|-----------|---------------------------------------|
| `homepage`               | ANY       | `/`                                   |
| `app_inscription`        | GET\|POST | `/inscription`                        |
| `app_connexion`          | GET\|POST | `/connexion`                          |
| `_logout_main`           | ANY       | `/deconnexion`                        |
| `app_utilisateur_list`   | ANY       | `/utilisateurs`                       |
| `app_utilisateur_show`   | GET       | `/utilisateurs/{login}`               |
| `app_utilisateur_edit`   | GET\|POST | `/utilisateur/{login}/modifier`       |
| `app_utilisateur_delete` | POST      | `/utilisateur/{login}/supprimer`      |
| `app_maintenance_page`   | ANY       | `/maintenance`                        |
| `app_utilisateur_json`   | GET       | `/utilisateur/{login}/json`           |

- L'ensemble des routes sont disponibles à l'aide de la commande suivante :
    ```bash
    php bin/console debug:router
    ```

## Contributions des membres de l'équipe

- **Chaïma Asiamar - [chaima.asiamar@etu.umontpellier.fr](mailto:chaima.asiamar@etu.umontpellier.fr)** : 33%
    - Édition des utilisateurs
    - Mode administrateur
    - Visuel
    - Review

- **Lilian Bramand - [lilian.bramand@etu.umontpellier.fr](mailto:lilian.bramand@etu.umontpellier.fr)** : 33%
    - Mise en place des tâches via Jira
    - Création de la structure du projet et du GitLab
    - Mise à jour des dates d'édition et de connexion
    - Visuel
    - Review
    - Test et Validation

- **Cédric Leretour - [cedric.leretour@etu.umontpellier.fr](mailto:cedric.leretour@etu.umontpellier.fr)** : 33%
    - Édition des utilisateurs
    - Gestion du code unique
    - Création des commandes Symfony
    - Mode maintenance
    - Export JSON
    - Review


---
