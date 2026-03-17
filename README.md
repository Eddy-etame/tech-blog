# Tech Blog

A full-featured tech blog built with Symfony 6.4, featuring user authentication, blogger workflows, admin moderation, email alerts, and modern JavaScript (Stimulus, Turbo).

---

## Table of Contents

- [Technologies Used](#technologies-used)
- [How to Launch the Project](#how-to-launch-the-project)
- [Project Structure](#project-structure)

---

## Technologies Used

This section explains every technology in the stack, how it is used in the project, and how you can apply it in your own work.

---

### 1. PHP 8.1

**What it is:** PHP is the server-side language that powers the application. PHP 8.1 introduces attributes, enums, readonly properties, and improved performance.

**How we use it:** All backend logic—controllers, entities, forms, services—is written in PHP. We use PHP 8 attributes for routing and Doctrine mapping instead of annotations or YAML.

**Example from the project:**

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

The `#[Route(...)]` attribute declares the URL and route name. PHP 8 attributes keep configuration close to the code.

---

### 2. Symfony 6.4 Framework

**What it is:** Symfony is a PHP framework that provides HTTP handling, dependency injection, configuration, and integration with many components (Forms, Security, Mailer, etc.).

**How we use it:** The entire application is built on Symfony. Controllers extend `AbstractController`, services are auto-wired, and configuration is in YAML.

**Key concepts:**

- **Controllers:** Handle HTTP requests and return responses.
- **Dependency injection:** Controllers receive repositories, the entity manager, and other services via constructor or method arguments.
- **Configuration:** `config/packages/` holds YAML config for each component.

**Example:**

```php
// Controllers receive services automatically
public function show(int $id, Request $request, PostRepository $postRepository, 
    PostAlertRepository $postAlertRepository, EntityManagerInterface $em): Response
{
    $post = $postRepository->find($id);
    // ...
}
```

Symfony injects `PostRepository`, `EntityManagerInterface`, etc. based on type hints—no manual wiring.

---

### 3. Doctrine ORM

**What it is:** Doctrine is an Object-Relational Mapper (ORM) that maps PHP objects to database tables. You work with objects; Doctrine generates SQL.

**How we use it:**

- **Entities:** PHP classes representing database tables (Post, User, Comment, Author, PostAlert).
- **Repositories:** Classes for querying entities (e.g. `PostRepository::findPaginatedAndSearch`).
- **Migrations:** Version-controlled schema changes.

**Example entity with Doctrine attributes:**

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

`#[ORM\Column]` defines a column; `#[ORM\ManyToOne]` and `#[ORM\OneToMany]` define relationships.

**Example repository query:**

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
    // ... pagination logic
}
```

Query Builder lets you build dynamic queries without raw SQL.

---

### 4. Symfony Form Component

**What it is:** A component for creating, validating, and rendering HTML forms. It binds form fields to PHP objects and handles CSRF.

**How we use it:** All forms (comments, posts, registration, login) use Symfony Form. We define form types that map to entities.

**Example form type:**

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

In the controller:

```php
$form = $this->createForm(PostType::class, $post);
$form->handleRequest($request);
if ($form->isSubmitted() && $form->isValid()) {
    $em->persist($post);
    $em->flush();
}
```

In Twig: `{{ form_start(form) }}`, `{{ form_row(form.title) }}`, etc.

---

### 5. Twig

**What it is:** A templating engine for PHP. It separates logic from presentation and provides inheritance, filters, and functions.

**How we use it:** All HTML is in `.twig` files. We use `extends`, `block`, `path()`, `asset()`, and filters like `|date`, `|length`.

**Example template:**

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

- `path('app_index')` generates the URL for the route named `app_index`.
- `post.author.firstName` accesses the related Author entity.
- `|date('d/m/Y')` formats the date.

---

### 6. Symfony Security

**What it is:** Handles authentication (login) and authorization (who can access what). Supports multiple firewalls and role-based access.

**How we use it:** We have three firewalls—`admin`, `blogger`, and `main`—each with its own login path and target. Access is controlled by `ROLE_ADMIN`, `ROLE_BLOGGER`, and `ROLE_USER`.

**Example configuration:**

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

In controllers:

```php
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController { ... }
```

`#[IsGranted('ROLE_ADMIN')]` restricts the controller to admins.

---

### 7. Symfony Mailer

**What it is:** Sends emails. Supports HTML and text templates, attachments, and async sending via Messenger.

**How we use it:** When an admin approves a post, we send an email to all subscribers of that author using `TemplatedEmail`.

**Example from AdminController:**

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

Configure `MAILER_DSN` in `.env` (e.g. `smtp://user:pass@smtp.example.com:587`).

---

### 8. Symfony Messenger

**What it is:** A message bus for async processing. Handlers run in the background (e.g. sending emails, processing jobs).

**How we use it:** By default, `SendEmailMessage` is routed to the `async` transport, so emails can be sent asynchronously. Configure `MESSENGER_TRANSPORT_DSN` and run `php bin/console messenger:consume async`.

**Example config:**

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

**What it is:** Manages frontend assets (CSS, JS) without Webpack. Uses native ES modules and an import map. No build step in development.

**How we use it:** CSS and JS live in `assets/`. The main entrypoint is `assets/app.js`, which imports `./styles/app.css` and `./bootstrap.js`. Twig loads it via `{{ importmap('app') }}`.

**Example:**

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

`importmap.php` defines the app entrypoint and third-party packages (Stimulus, Turbo).

---

### 10. Stimulus

**What it is:** A lightweight JavaScript framework by Hotwired. It connects JavaScript behavior to HTML via `data-controller`, `data-action`, and `data-*-target` attributes.

**How we use it:** The comment form uses a Stimulus controller to submit via AJAX and update the page without a full reload.

**Example controller:**

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
        // Update DOM with new comment and form
    }
}
```

**In the template:**

```twig
{{ form_start(form, {
    'attr': {
        'data-controller': 'comment-form',
        'data-action': 'submit.prevent->comment-form#handleSubmit'
    }
}) }}
```

`data-controller="comment-form"` loads the controller; `data-action="submit.prevent->comment-form#handleSubmit"` calls `handleSubmit` on form submit, with `prevent` stopping the default submit.

---

### 11. Turbo (Hotwired Turbo)

**What it is:** Turbo speeds up navigation by fetching pages via AJAX and swapping only the `<body>` content. It can also handle Turbo Streams for partial updates.

**How we use it:** Turbo is loaded via the import map. Page links and form submissions can be enhanced by Turbo for faster navigation. The comment form disables Turbo (`data-turbo="false"`) and uses our Stimulus controller for finer control.

---

### 12. Doctrine Migrations

**What it is:** Version-controlled database schema changes. Each migration is a PHP class that defines `up()` and `down()`.

**How we use it:** After changing entities, run `php bin/console make:migration` to generate a migration, then `php bin/console doctrine:migrations:migrate` to apply it.

**Example migration:**

```php
// migrations/Version20260317000000.php
public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE user ADD is_blocked TINYINT(1) DEFAULT 0 NOT NULL');
}
```

---

### 13. Composer

**What it is:** PHP’s dependency manager. `composer.json` lists packages; `composer.lock` pins versions for reproducible installs.

**How we use it:** Run `composer install` to install dependencies. Dev tools (Maker, Web Profiler) are in `require-dev`.

---

### 14. PHPUnit

**What it is:** PHP’s testing framework. Used for unit and functional tests.

**How we use it:** Tests live in `tests/`. Run `php bin/phpunit` to execute them. Functional tests can use `WebTestCase` to simulate HTTP requests.

---

### 15. PostgreSQL (or MySQL/SQLite)

**What it is:** The database. Doctrine supports PostgreSQL, MySQL, MariaDB, and SQLite.

**How we use it:** `DATABASE_URL` in `.env` defines the connection. The project includes `compose.yaml` for a PostgreSQL container.

---

## How to Launch the Project

### Prerequisites

- PHP 8.1+
- Composer
- PostgreSQL (or MySQL/SQLite)
- Symfony CLI (optional, for `symfony server:start`)

### 1. Clone and install dependencies

```bash
git clone https://github.com/Eddy-etame/tech-blog.git
cd tech-blog
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` and set:

- `APP_SECRET`: A random 32-character string
- `DATABASE_URL`: Your database connection string

Example for PostgreSQL:

```
DATABASE_URL="postgresql://app:password@127.0.0.1:5432/tech_blog?serverVersion=16&charset=utf8"
```

Example for MySQL:

```
DATABASE_URL="mysql://app:password@127.0.0.1:3306/tech_blog?serverVersion=8.0&charset=utf8mb4"
```

### 3. Create the database and run migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. (Optional) Seed sample data

```bash
php bin/console app:seed-data
```

### 5. Start the web server

**Option A – Symfony CLI (recommended):**

```bash
symfony server:start
```

Use the URL shown (typically `http://127.0.0.1:8001`). Do not use port 8000 unless configured; another service may use it and assets may fail.

**Option B – PHP built-in server:**

```bash
php -S 127.0.0.1:8001 -t public
```

### 6. (Optional) Run with Docker

```bash
docker compose up -d
```

Then set `DATABASE_URL` to match the Docker database (e.g. `postgresql://app:!ChangeMe!@database:5432/app`).

---

## Project Structure

```
tech-blog/
├── assets/                 # Frontend assets (CSS, JS, Stimulus controllers)
├── config/                 # Symfony configuration
│   └── packages/           # Per-component config (security, doctrine, etc.)
├── migrations/             # Doctrine migrations
├── public/                 # Web root (index.php, assets output)
├── src/
│   ├── Controller/         # HTTP controllers
│   ├── Entity/             # Doctrine entities
│   ├── Form/               # Form types
│   ├── Repository/         # Doctrine repositories
│   └── EventListener/      # Event listeners (e.g. auth failure)
├── templates/              # Twig templates
│   ├── admin/              # Admin dashboard templates
│   ├── blogger/            # Blogger dashboard templates
│   ├── page/               # Public pages (home, post show)
│   └── email/              # Email templates
├── tests/                  # PHPUnit tests
├── .env.example            # Environment template (no secrets)
├── composer.json           # PHP dependencies
└── importmap.php           # JavaScript import map
```

---

## License

Proprietary.
