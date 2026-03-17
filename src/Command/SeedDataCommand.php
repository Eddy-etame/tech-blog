<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-data',
    description: 'Seed the database with sample authors, posts, comments and users.',
)]
class SeedDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('reset', null, InputOption::VALUE_NONE, 'Clear existing data before seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('reset')) {
            $this->em->createQuery('DELETE FROM App\Entity\Comment')->execute();
            $this->em->createQuery('DELETE FROM App\Entity\Post')->execute();
            $this->em->createQuery('DELETE FROM App\Entity\Author')->execute();
            $this->em->createQuery('DELETE FROM App\Entity\User')->execute();
            $this->em->flush();
            $io->note('Données existantes supprimées.');
        }

        $adminUser = new User();
        $adminUser->setEmail('admin@techblog.local');
        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, 'admin123'));
        $adminUser->setRoles([User::ROLE_ADMIN]);
        $adminUser->setIsVerified(true);
        $this->em->persist($adminUser);

        $bloggerUser = new User();
        $bloggerUser->setEmail('blogger@techblog.local');
        $bloggerUser->setPassword($this->passwordHasher->hashPassword($bloggerUser, 'blogger123'));
        $bloggerUser->setRoles([User::ROLE_BLOGGER]);
        $bloggerUser->setIsVerified(true);
        $this->em->persist($bloggerUser);

        $author1 = new Author();
        $author1->setFirstName('Eddy');
        $author1->setLastName('Etame');
        $author1->setUser($bloggerUser);
        $bloggerUser->setAuthor($author1);
        $this->em->persist($author1);

        $author2 = new Author();
        $author2->setFirstName('King');
        $author2->setLastName('E');
        $this->em->persist($author2);

        $author3 = new Author();
        $author3->setFirstName('Steve');
        $author3->setLastName('Jobs');
        $this->em->persist($author3);

        $authors = [$author1, $author2, $author3];
        $posts = [
            [
                'title' => 'L\'intelligence artificielle générative en 2025 : état des lieux',
                'excerpt' => 'Les LLM ont transformé la façon dont nous concevons les logiciels. État des lieux et perspectives.',
                'content' => "Les modèles de langage à grande échelle (LLM) ont transformé la façon dont nous concevons et utilisons les logiciels. ChatGPT, Claude, Gemini et les modèles open-source comme Llama continuent d'évoluer à un rythme soutenu.\n\nEn 2025, les capacités multimodales sont devenues la norme : analyse d'images, génération de code, synthèse vocale et compréhension de documents complexes. Les entreprises intègrent l'IA dans leurs flux de travail, de la rédaction de rapports à l'assistance au développement.\n\nLes enjeux restent nombreux : coûts de calcul, confidentialité des données, hallucinations et biais. Les techniques de fine-tuning et de RAG (Retrieval-Augmented Generation) permettent d'adapter les modèles à des contextes métier spécifiques tout en limitant les dérives.\n\nPour les développeurs, les outils d'IA assistée (Copilot, Cursor, Codeium) sont désormais incontournables. La productivité augmente significativement sur les tâches répétitives, mais la compréhension approfondie du code et de l'architecture reste essentielle.\n\nL'évolution des modèles open-source permet désormais à des équipes de taille moyenne de fine-tuner des modèles spécialisés sans budget cloud conséquent. Les techniques de distillation et de quantisation réduisent les coûts d'inférence. La régulation européenne (AI Act) et les chartes d'éthique imposent une réflexion sur l'usage responsable de ces technologies.\n\nLes agents autonomes et les chaînes de raisonnement (chain-of-thought) ouvrent de nouvelles perspectives pour l'automatisation complexe. Les benchmarks comme MMLU et HumanEval permettent de comparer objectivement les modèles. En production, le monitoring des coûts par requête et la gestion des quotas API deviennent des compétences clés pour les équipes techniques.",
                'daysAgo' => 6,
                'authorIndex' => 0,
                'createdByUser' => true,
            ],
            [
                'title' => 'Angular 19 : signaux, contrôleurs standalone et performances',
                'excerpt' => 'Signaux, composants standalone et optimisations : Angular 19 en détail.',
                'content' => "Angular 19 marque une étape importante dans l'évolution du framework. Les signaux (signals), inspirés de Solid.js et Vue 3, offrent une réactivité granulaire et performante sans Zone.js.\n\nLes composants standalone sont désormais le modèle par défaut. Plus besoin de déclarer les modules : chaque composant peut importer directement ses dépendances. La structure des applications Angular en est simplifiée.\n\nLes améliorations de performance touchent le rendu incrémental (incremental hydration) pour le SSR, réduisant le temps de First Contentful Paint. Le nouveau compilateur expérimental améliore la taille des bundles et le temps de compilation.\n\nPour migrer une application existante, la stratégie recommandée est progressive : activer les signaux sur les nouveaux composants, migrer les NgModules vers le standalone, puis envisager la désactivation de Zone.js une fois la migration terminée.\n\nLa nouvelle API de contrôleurs (control flow) avec @if, @for et @switch simplifie les templates. Les deferred views permettent de charger des composants à la demande, améliorant le temps de chargement initial. La compatibilité avec les bibliothèques existantes reste assurée grâce à l'injection de Zone.js optionnelle.\n\nLes améliorations du router incluent les route guards asynchrones et le prefetching des routes. L'injection d'environnements (inject()) remplace progressivement le constructeur pour les dépendances. Les schematics officiels (ng add, ng generate) s'adaptent à ces changements. Pour les équipes en migration, la documentation Angular fournit des guides détaillés et des exemples de code pour chaque étape.",
                'daysAgo' => 5,
                'authorIndex' => 0,
                'createdByUser' => true,
            ],
            [
                'title' => 'Symfony 7 et PHP 8.4 : construire des APIs modernes',
                'excerpt' => 'Symfony 7 et PHP 8.4 : nouvelles fonctionnalités pour des APIs performantes.',
                'content' => "Symfony 7, sorti fin 2024, s'appuie sur PHP 8.4 et apporte des améliorations significatives. Le composant HttpFoundation gère mieux les requêtes asynchrones, et le nouveau système de configuration simplifie le déploiement.\n\nPHP 8.4 introduit les propriétés hook (property hooks), une syntaxe plus expressive pour les getters et setters. Les améliorations du JIT et du garbage collector réduisent la consommation mémoire des applications longues.\n\nPour les APIs REST ou GraphQL, Symfony avec API Platform reste un choix solide. La sérialisation, la validation et la documentation OpenAPI sont intégrées nativement. Les attributs PHP 8 permettent une configuration déclarative et lisible.\n\nLes bonnes pratiques en 2025 : utiliser les enumerations pour les statuts, les readonly properties pour l'immutabilité, et profiter des fibres (fibers) pour le traitement concurrent des requêtes I/O.\n\nLe composant Messenger et les workers asynchrones permettent de découpler les traitements longs. L'intégration avec Mercure ou les WebSockets facilite le temps réel. Le système de cache et le reverse proxy HTTP de Symfony optimisent les performances en production.\n\nLe composant UID offre des identifiants uniques (UUID, ULID) pour les entités distribuées. Les profilers de performance et les outils de debug (VarDumper, Monolog) restent des atouts pour le développement. La communauté Symfony maintient une documentation à jour et des best practices partagées via les SymfonyCon et les meetups locaux.",
                'daysAgo' => 4,
                'authorIndex' => 1,
            ],
            [
                'title' => 'WebAssembly : exécuter du code natif dans le navigateur',
                'excerpt' => 'WASM et WASI : exécuter du code natif dans le navigateur et au-delà.',
                'content' => "WebAssembly (WASM) permet d'exécuter du code compilé à des vitesses proches du natif, directement dans le navigateur. Les cas d'usage vont des applications de productivité (Figma, Google Earth) aux jeux et aux outils de développement.\n\nWASI (WebAssembly System Interface) étend WASM au-delà du navigateur : exécution côté serveur, edge computing, et même des runtimes comme Wasmtime ou Wasmer. Les applications peuvent être déployées une fois et exécutées partout.\n\nLes langages supportés se multiplient : Rust, C/C++, Go, et désormais des expérimentations avec Python et Java. Les bindings avec JavaScript permettent une interopérabilité fluide.\n\nPour les développeurs web, WASM n'est pas un remplacement de JavaScript mais un complément. Utilisez-le pour les calculs intensifs, le traitement d'images ou la cryptographie. Le reste de l'application peut rester en JS/TS pour une meilleure maintenabilité.\n\nLes composants WebAssembly (Wasm Component Model) standardisent l'échange de données entre modules. Les outils comme wasm-pack et Emscripten simplifient la compilation. La sécurité par défaut (sandboxing, pas d'accès direct à la mémoire) en fait un choix adapté pour l'exécution de code tiers.\n\nLes frameworks comme Spin (Fermyon) et wasmCloud permettent de déployer des applications WASM en production. Les extensions navigateur et les plugins peuvent désormais être écrits en WASM pour une meilleure isolation. L'écosystème continue de croître avec des initiatives comme le Bytecode Alliance qui promeut des standards ouverts et interopérables.",
                'daysAgo' => 3,
                'authorIndex' => 1,
            ],
            [
                'title' => 'Edge computing et serverless : l\'infrastructure invisible',
                'excerpt' => 'Edge et serverless : réduire la latence et les coûts d\'infrastructure.',
                'content' => "L'edge computing rapproche le calcul des utilisateurs. Au lieu d'exécuter tout sur un serveur central, les fonctions s'exécutent sur des points de présence (PoP) répartis dans le monde. La latence diminue, et la résilience augmente.\n\nLes fournisseurs (Cloudflare Workers, Vercel Edge, AWS Lambda@Edge) proposent des runtimes JavaScript, WebAssembly ou même Python. Les coûts sont facturés à l'usage, sans serveur à maintenir.\n\nLes cas d'usage typiques : personnalisation de contenu, A/B testing, géolocalisation, authentification, et pré-rendu de pages. Les APIs peuvent répondre en quelques millisecondes depuis la région la plus proche.\n\nLes défis : cold starts, limites d'exécution, et debugging distribué. Les bonnes pratiques incluent le warming des fonctions, la réduction de la taille des bundles, et une stratégie de fallback vers l'origine en cas d'échec.\n\nLes Durable Objects de Cloudflare et les Workers avec état permettent des applications stateful à l'edge. La combinaison edge + CDN + origin offre une architecture résiliente et performante pour les applications globales.\n\nLes observabilités (logs, métriques, traces) sont désormais disponibles pour les runtimes edge. Les tests locaux avec Miniflare ou les outils fournisseurs simulent l'environnement edge. Pour les équipes DevOps, l'intégration CI/CD avec déploiement automatique vers les PoP est devenue un standard.",
                'daysAgo' => 2,
                'authorIndex' => 2,
            ],
            [
                'title' => 'Outils de développement 2025 : DX, IA et productivité',
                'excerpt' => 'IDE, tests, debugging : l\'expérience développeur en 2025.',
                'content' => "L'expérience développeur (DX) est devenue un critère de choix majeur. Les IDE modernes (VS Code, Cursor, JetBrains) intègrent l'IA pour l'autocomplétion, la génération de code et l'explication de fonctions complexes.\n\nLes outils de test évoluent : Playwright pour l'E2E, Vitest pour l'unité, et des frameworks comme Storybook pour le développement de composants en isolation. La couverture et la vitesse des tests sont des priorités.\n\nLe debugging bénéficie des time-travel debuggers, des traces distribuées (OpenTelemetry) et des profils de performance intégrés. Identifier une régression ou un goulot d'étranglement prend moins de temps.\n\nEnfin, la documentation et la collaboration : les ADR (Architecture Decision Records), les RFC internes et les outils comme Notion ou GitBook permettent de capitaliser le savoir. Une bonne DX réduit la friction à l'onboarding et améliore la vélocité des équipes.\n\nLes environnements de développement conteneurisés (Dev Containers, GitHub Codespaces) standardisent les setups. Les outils de revue de code assistés par l'IA détectent les vulnérabilités et suggèrent des améliorations. La productivité individuelle et collective s'en trouve renforcée.\n\nLes extensions comme GitHub Copilot Workspace et les assistants de terminal (Warp, Fig) accélèrent les workflows. Les outils de monitoring (Sentry, Datadog) intègrent le code source pour un debugging rapide en production. Les équipes qui investissent dans la DX constatent une réduction du turnover et une amélioration de la qualité du code.",
                'daysAgo' => 1,
                'authorIndex' => 2,
            ],
        ];

        $postEntities = [];
        foreach ($posts as $data) {
            $post = new Post();
            $post->setTitle($data['title']);
            $post->setContent($data['content']);
            $post->setExcerpt($data['excerpt'] ?? null);
            $post->setStatus(Post::STATUS_PUBLISHED);
            $post->setPublishedAt((new \DateTime())->modify("-{$data['daysAgo']} days"));
            $post->setAuthor($authors[$data['authorIndex']]);
            if (!empty($data['createdByUser'])) {
                $post->setCreatedBy($bloggerUser);
            }
            $this->em->persist($post);
            $postEntities[] = $post;
        }

        $comments = [
            ['content' => 'Très bon résumé sur l\'état de l\'IA en 2025. Les RAG sont effectivement essentiels pour limiter les hallucinations.', 'author' => 'Jean Martin', 'hoursAgo' => 48, 'postIndex' => 0],
            ['content' => "J'utilise Cursor au quotidien, la productivité a vraiment augmenté. Merci pour l'article.", 'author' => 'Sophie Leroy', 'hoursAgo' => 24, 'postIndex' => 0],
            ['content' => 'La migration vers les signaux Angular est en cours chez nous. Les perfs sont au rendez-vous.', 'author' => 'Pierre Bernard', 'hoursAgo' => 18, 'postIndex' => 1],
            ['content' => 'API Platform + Symfony 7, combo imbattable pour des APIs robustes.', 'author' => 'Claire Dubois', 'hoursAgo' => 12, 'postIndex' => 2],
            ['content' => 'WASM pour le traitement d\'images dans notre app, résultats bluffants côté performance.', 'author' => 'Lucas Petit', 'hoursAgo' => 6, 'postIndex' => 3],
        ];

        foreach ($comments as $data) {
            $comment = new Comment();
            $comment->setContent($data['content']);
            $comment->setAuthorName($data['author']);
            $comment->setCreatedAt((new \DateTime())->modify("-{$data['hoursAgo']} hours"));
            $comment->setPost($postEntities[$data['postIndex']]);
            $this->em->persist($comment);
        }

        $this->em->flush();

        $io->success(sprintf(
            'Données insérées : 2 utilisateurs (admin@techblog.local / blogger@techblog.local), %d auteurs, %d articles, %d commentaires. Mots de passe : admin123 / blogger123',
            3,
            count($posts),
            count($comments)
        ));

        return Command::SUCCESS;
    }
}
