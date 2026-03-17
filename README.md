# Tech Blog

Un blog tech complet construit avec Symfony 6.4 : authentification utilisateur, workflows blogger, modération admin, alertes par email et JavaScript moderne (Stimulus, Turbo).

---

## Table des matières

- [Technologies utilisées](#technologies-utilisées)
- [Lancer le projet](#lancer-le-projet)
- [Structure du projet](#structure-du-projet)

---

## Technologies utilisées

Cette section décrit chaque technologie du projet, son utilisation et comment l'appliquer dans vos propres développements.

---

### 1. PHP 8.1

**C'est quoi :** PHP est le langage côté serveur qui fait tourner l'application. PHP 8.1 introduit les attributs, les enums, les propriétés readonly et de meilleures performances.

**Utilisation dans le projet :** Toute la logique backend (contrôleurs, entités, formulaires, services) est en PHP. On utilise les attributs PHP 8 pour le routage et le mapping Doctrine au lieu des annotations ou du YAML.

**Exemple tiré du projet :**

```php
// src/Controller/PageController.php
#[Route('/', name: 'app_index')]
public function index(Request $request, PostRepository $postRepository): Response
{
    $page = max(1, (int) $request->query->get('page', 1));
    $search = $request->query->get('q');
    $result = $postRepository->findPaginatedAndSearch($page, $search);
    return $this->render('page/index.html.twig', [
        'posts' => $result['posts'],
        'total' => $result['total'],
        'pages' => $result['pages'],
        'current_page' => $result['page'],
        'search' => $search,
    ]);
}
```

L'attribut `#[Route(...)]` déclare l'URL et le nom de la route. Les attributs PHP 8 gardent la configuration proche du code.

---

### 2. Symfony 6.4

**C'est quoi :** Symfony est un framework PHP qui fournit la gestion HTTP, l'injection de dépendances, la configuration et l'intégration de nombreux composants (Forms, Security, Mailer, etc.).

**Utilisation dans le projet :** Toute l'application repose sur Symfony. Les contrôleurs étendent `AbstractController`, les services sont auto-injectés et la configuration est en YAML.

**Concepts clés :**

- **Contrôleurs :** Gèrent les requêtes HTTP et renvoient des réponses.
- **Injection de dépendances :** Les contrôleurs reçoivent les repositories, l'EntityManager et d'autres services via le constructeur ou les arguments de méthode.
- **Configuration :** `config/packages/` contient la config YAML de chaque composant.

**Exemple :**

```php
// Les contrôleurs reçoivent les services automatiquement
public function show(int $id, Request $request, PostRepository $postRepository, 
    PostAlertRepository $postAlertRepository, EntityManagerInterface $em): Response
{
    $post = $postRepository->find($id);
    // ...
}
```

Symfony injecte `PostRepository`, `EntityManagerInterface`, etc. selon les types déclarés, sans câblage manuel.

---

### 3. Doctrine ORM

**C'est quoi :** Doctrine est un ORM (Object-Relational Mapper) qui mappe les objets PHP aux tables de base de données. On travaille avec des objets ; Doctrine génère le SQL.

**Utilisation dans le projet :**

- **Entités :** Classes PHP représentant les tables (Post, User, Comment, Author, PostAlert).
- **Repositories :** Classes pour interroger les entités (ex. `PostRepository::findPaginatedAndSearch`).
- **Migrations :** Changements de schéma versionnés.

**Exemple d'entité avec les attributs Doctrine :**

```php
// src/Entity/Post.php
#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Author $author = null;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', cascade: ['persist', 'remove'])]
    private Collection $comments;
}
```

`#[ORM\Column]` définit une colonne ; `#[ORM\ManyToOne]` et `#[ORM\OneToMany]` définissent les relations.

**Exemple de requête dans un repository :**

```php
// src/Repository/PostRepository.php
public function findPaginatedAndSearch(int $page = 1, ?string $search = null): array
{
    $qb = $this->createQueryBuilder('p')
        ->orderBy('p.publishedAt', 'DESC')
        ->andWhere('p.status = :status')
        ->setParameter('status', Post::STATUS_PUBLISHED);

    if (null !== $search && '' !== trim($search)) {
        $qb->andWhere('(p.title LIKE :search OR p.content LIKE :search)')
            ->setParameter('search', '%' . trim($search) . '%');
    }
    // ... logique de pagination
}
```

Le Query Builder permet de construire des requêtes dynamiques sans SQL brut.

---

### 4. Symfony Form

**C'est quoi :** Un composant pour créer, valider et afficher des formulaires HTML. Il lie les champs aux objets PHP et gère le CSRF.

**Utilisation dans le projet :** Tous les formulaires (commentaires, articles, inscription, connexion) utilisent Symfony Form. On définit des types de formulaire mappés aux entités.

**Exemple de type de formulaire :**

```php
// src/Form/PostType.php
class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Titre de l\'article'],
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Extrait (optionnel)',
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => ['rows' => 15],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Post::class]);
    }
}
```

Dans le contrôleur :

```php
$form = $this->createForm(PostType::class, $post);
$form->handleRequest($request);
if ($form->isSubmitted() && $form->isValid()) {
    $em->persist($post);
    $em->flush();
}
```

Dans Twig : `{{ form_start(form) }}`, `{{ form_row(form.title) }}`, etc.

---

### 5. Twig

**C'est quoi :** Un moteur de templates pour PHP. Il sépare la logique de la présentation et fournit l'héritage, les filtres et les fonctions.

**Utilisation dans le projet :** Tout le HTML est dans des fichiers `.twig`. On utilise `extends`, `block`, `path()`, `asset()` et des filtres comme `|date`, `|length`.

**Exemple de template :**

```twig
{# templates/page/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Accueil - Tech Blog{% endblock %}

{% block body %}
    <section class="hero">
        <h1>Bienvenue sur Tech Blog</h1>
        <form action="{{ path('app_index') }}" method="get" class="search-form">
            <input type="search" name="q" value="{{ search|default('') }}" placeholder="Rechercher...">
            <button type="submit" class="btn">Rechercher</button>
        </form>
    </section>

    {% for post in posts %}
        <article>
            <h3><a href="{{ path('app_post_show', { id: post.id }) }}">{{ post.title }}</a></h3>
            <p>Par {{ post.author ? post.author.firstName ~ ' ' ~ post.author.lastName : 'Anonyme' }}</p>
            <p>Publié le {{ post.publishedAt|date('d/m/Y') }}</p>
        </article>
    {% endfor %}
{% endblock %}
```

- `path('app_index')` génère l'URL de la route nommée `app_index`.
- `post.author.firstName` accède à l'entité Author liée.
- `|date('d/m/Y')` formate la date.

---

### 6. Symfony Security

**C'est quoi :** Gère l'authentification (connexion) et l'autorisation (qui peut accéder à quoi). Supporte plusieurs firewalls et l'accès par rôles.

**Utilisation dans le projet :** Trois firewalls — `admin`, `blogger` et `main` — chacun avec son chemin de connexion et sa cible. L'accès est contrôlé par `ROLE_ADMIN`, `ROLE_BLOGGER` et `ROLE_USER`.

**Exemple de configuration :**

```yaml
# config/packages/security.yaml
security:
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        admin:
            pattern: ^/admin
            form_login:
                login_path: admin_login
                check_path: admin_login_check
                default_target_path: admin_dashboard
            logout:
                path: /admin/logout
                target: /

    access_control:
        - { path: ^/admin/, roles: ROLE_ADMIN }
        - { path: ^/blogger, roles: ROLE_BLOGGER }
        - { path: ^/alerts, roles: ROLE_USER }
```

Dans les contrôleurs :

```php
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController { ... }
```

`#[IsGranted('ROLE_ADMIN')]` restreint le contrôleur aux admins.

---

### 7. Symfony Mailer

**C'est quoi :** Envoie des emails. Supporte les templates HTML et texte, les pièces jointes et l'envoi asynchrone via Messenger.

**Utilisation dans le projet :** Quand un admin approuve un article, on envoie un email à tous les abonnés de l'auteur avec `TemplatedEmail`.

**Exemple tiré d'AdminController :**

```php
$email = (new TemplatedEmail())
    ->from('noreply@example.com')
    ->to($user->getEmail())
    ->subject('Nouvel article de ' . $authorName . ' : ' . $post->getTitle())
    ->htmlTemplate('email/post_alert.html.twig')
    ->textTemplate('email/post_alert.txt.twig')
    ->context([
        'authorName' => $authorName,
        'postTitle' => $post->getTitle(),
        'excerpt' => $excerpt,
        'postUrl' => $postUrl,
        'manageAlertsUrl' => $manageAlertsUrl,
    ]);
$mailer->send($email);
```

Configurer `MAILER_DSN` dans `.env` (ex. `smtp://user:pass@smtp.example.com:587`).

---

### 8. Symfony Messenger

**C'est quoi :** Un bus de messages pour le traitement asynchrone. Les handlers s'exécutent en arrière-plan (envoi d'emails, jobs, etc.).

**Utilisation dans le projet :** Par défaut, `SendEmailMessage` est routé vers le transport `async`, donc les emails peuvent être envoyés de façon asynchrone. Configurer `MESSENGER_TRANSPORT_DSN` et lancer `php bin/console messenger:consume async`.

**Exemple de config :**

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            Symfony\Component\Mailer\Messenger\SendEmailMessage: async
```

---

### 9. Symfony Asset Mapper

**C'est quoi :** Gère les assets frontend (CSS, JS) sans Webpack. Utilise les modules ES natifs et une import map. Pas d'étape de build en développement.

**Utilisation dans le projet :** CSS et JS sont dans `assets/`. Le point d'entrée principal est `assets/app.js`, qui importe `./styles/app.css` et `./bootstrap.js`. Twig le charge via `{{ importmap('app') }}`.

**Exemple :**

```javascript
// assets/app.js
import './bootstrap.js';
import './styles/app.css';
```

```twig
{# templates/base.html.twig #}
{% block javascripts %}
    {{ importmap('app') }}
{% endblock %}
```

`importmap.php` définit le point d'entrée et les paquets tiers (Stimulus, Turbo).

---

### 10. Stimulus

**C'est quoi :** Un framework JavaScript léger par Hotwired. Il relie le comportement JavaScript au HTML via les attributs `data-controller`, `data-action` et `data-*-target`.

**Utilisation dans le projet :** Le formulaire de commentaire utilise un contrôleur Stimulus pour soumettre en AJAX et mettre à jour la page sans rechargement complet.

**Exemple de contrôleur :**

```javascript
// assets/controllers/comment_form_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['submitBtn', 'btnText'];

    async handleSubmit(event) {
        event.preventDefault();
        const form = this.element;
        const formData = new FormData(form);

        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        // Mise à jour du DOM avec le nouveau commentaire et le formulaire
    }
}
```

**Dans le template :**

```twig
{{ form_start(form, {
    'attr': {
        'data-controller': 'comment-form',
        'data-action': 'submit.prevent->comment-form#handleSubmit'
    }
}) }}
```

`data-controller="comment-form"` charge le contrôleur ; `data-action="submit.prevent->comment-form#handleSubmit"` appelle `handleSubmit` à la soumission, avec `prevent` qui bloque l'envoi par défaut.

---

### 11. Turbo (Hotwired Turbo)

**C'est quoi :** Turbo accélère la navigation en récupérant les pages en AJAX et en remplaçant uniquement le contenu du `<body>`. Il gère aussi les Turbo Streams pour des mises à jour partielles.

**Utilisation dans le projet :** Turbo est chargé via l'import map. Les liens et formulaires peuvent être améliorés par Turbo pour une navigation plus rapide. Le formulaire de commentaire désactive Turbo (`data-turbo="false"`) et utilise notre contrôleur Stimulus pour un contrôle plus fin.

---

### 12. Doctrine Migrations

**C'est quoi :** Changements de schéma de base de données versionnés. Chaque migration est une classe PHP avec `up()` et `down()`.

**Utilisation dans le projet :** Après modification des entités, exécuter `php bin/console make:migration` pour générer une migration, puis `php bin/console doctrine:migrations:migrate` pour l'appliquer.

**Exemple de migration :**

```php
// migrations/Version20260317000000.php
public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE user ADD is_blocked TINYINT(1) DEFAULT 0 NOT NULL');
}
```

---

### 13. Composer

**C'est quoi :** Le gestionnaire de dépendances PHP. `composer.json` liste les paquets ; `composer.lock` fige les versions pour des installations reproductibles.

**Utilisation dans le projet :** Exécuter `composer install` pour installer les dépendances. Les outils de dev (Maker, Web Profiler) sont dans `require-dev`.

---

### 14. PHPUnit

**C'est quoi :** Le framework de tests PHP. Utilisé pour les tests unitaires et fonctionnels.

**Utilisation dans le projet :** Les tests sont dans `tests/`. Exécuter `php bin/phpunit` pour les lancer. Les tests fonctionnels peuvent utiliser `WebTestCase` pour simuler des requêtes HTTP.

---

### 15. PostgreSQL (ou MySQL/SQLite)

**C'est quoi :** La base de données. Doctrine supporte PostgreSQL, MySQL, MariaDB et SQLite.

**Utilisation dans le projet :** `DATABASE_URL` dans `.env` définit la connexion. Le projet inclut `compose.yaml` pour un conteneur PostgreSQL.

---

## Lancer le projet

### Prérequis

- PHP 8.1+
- Composer
- PostgreSQL (ou MySQL/SQLite)
- Symfony CLI (optionnel, pour `symfony server:start`)

### 1. Cloner et installer les dépendances

```bash
git clone https://github.com/Eddy-etame/tech-blog.git
cd tech-blog
composer install
```

### 2. Configurer l'environnement

```bash
cp .env.example .env
```

Modifier `.env` et définir :

- `APP_SECRET` : une chaîne aléatoire de 32 caractères
- `DATABASE_URL` : la chaîne de connexion à la base de données

Exemple pour PostgreSQL :

```
DATABASE_URL="postgresql://app:password@127.0.0.1:5432/tech_blog?serverVersion=16&charset=utf8"
```

Exemple pour MySQL :

```
DATABASE_URL="mysql://app:password@127.0.0.1:3306/tech_blog?serverVersion=8.0&charset=utf8mb4"
```

### 3. Créer la base de données et exécuter les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. (Optionnel) Charger des données de démonstration

```bash
php bin/console app:seed-data
```

### 5. Démarrer le serveur web

**Option A – Symfony CLI (recommandé) :**

```bash
symfony server:start
```

Utiliser l'URL affichée (généralement `http://127.0.0.1:8001`). Ne pas utiliser le port 8000 sauf configuration spécifique ; un autre service peut l'utiliser et les assets peuvent échouer.

**Option B – Serveur PHP intégré :**

```bash
php -S 127.0.0.1:8001 -t public
```

### 6. (Optionnel) Lancer avec Docker

```bash
docker compose up -d
```

Puis adapter `DATABASE_URL` pour la base Docker (ex. `postgresql://app:!ChangeMe!@database:5432/app`).

---

## Structure du projet

```
tech-blog/
├── assets/                 # Assets frontend (CSS, JS, contrôleurs Stimulus)
├── config/                 # Configuration Symfony
│   └── packages/           # Config par composant (security, doctrine, etc.)
├── migrations/             # Migrations Doctrine
├── public/                 # Racine web (index.php, sortie des assets)
├── src/
│   ├── Controller/         # Contrôleurs HTTP
│   ├── Entity/             # Entités Doctrine
│   ├── Form/               # Types de formulaires
│   ├── Repository/         # Repositories Doctrine
│   └── EventListener/      # Écouteurs d'événements (ex. échec d'auth)
├── templates/              # Templates Twig
│   ├── admin/              # Templates du tableau de bord admin
│   ├── blogger/            # Templates du tableau de bord blogger
│   ├── page/               # Pages publiques (accueil, affichage article)
│   └── email/              # Templates d'emails
├── tests/                  # Tests PHPUnit
├── .env.example            # Modèle d'environnement (sans secrets)
├── composer.json           # Dépendances PHP
└── importmap.php           # Import map JavaScript
```

---

## Licence

Propriétaire.
