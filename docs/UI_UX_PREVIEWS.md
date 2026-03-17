# UI/UX Ideas – Visual Previews

Before implementing each feature, here’s how it will look and behave on the site.

---

## 1. Hero Section

**Placement:** Full-width block at the top of the homepage, between the header and the main content.

**Layout:**
```
┌─────────────────────────────────────────────────────────────────┐
│  [Header: Symfony Blog | Lumière | Accueil | À propos | Legal]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│     ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   │
│     ░  Subtle purple/indigo gradient mesh (dark) or soft        ░ │
│     ░  lavender (light theme)                                   ░ │
│     ░                                                            ░ │
│     ░         Bienvenue sur Symfony Blog                        ░ │
│     ░         (Large serif heading, 2.5rem)                     ░ │
│     ░                                                            ░ │
│     ░    Découvrez nos articles sur Symfony, Doctrine et Twig   ░ │
│     ░    (Muted subtitle, 1.1rem)                                ░ │
│     ░                                                            ░ │
│     ░         [  Rechercher un article...  ] [Rechercher]        ░ │
│     ░         (Search bar centered in hero)                      ░ │
│     ░                                                            ░ │
│     ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   │
│                                                                   │
├─────────────────────────────────────────────────────────────────┤
│  Liste des articles                                              │
│  [Article cards...]                                              │
└─────────────────────────────────────────────────────────────────┘
```

**Visual details:**
- Height: ~280–320px
- Background: Gradient mesh (purple/indigo in dark mode, soft lavender in light)
- Typography: Playfair Display for the main title, DM Sans for the subtitle
- Search bar: Centered, slightly larger, with a subtle shadow
- Padding: ~3rem vertical, 2rem horizontal
- Optional: Light fade-in animation on load

---

## 2. Sun/Moon Icons for Theme Toggle

**Current:** Text buttons "Lumière" / "Sombre"

**Proposed:**
```
[  ☀️  ]  or  [  🌙  ]   (icon only, or icon + short label)
```

- Dark mode: Show sun icon (click to switch to light)
- Light mode: Show moon icon (click to switch to dark)
- Size: ~24×24px
- Hover: Slight scale (1.05) and accent color
- Optional: Short crossfade when switching

---

## 3. Staggered Entrance Animations for Article Cards

**Current:** Cards appear together with a simple fade-in.

**Proposed:**
- Each card fades in and moves up slightly (e.g. 15px)
- Stagger: ~80ms between cards
- Duration: ~0.4s per card
- Effect: Cards appear one after another from top to bottom

---

## 4. Reading Progress Bar (Article Pages)

**Placement:** Fixed bar at the top of the viewport, under the header.

**Layout:**
```
┌─────────────────────────────────────────────────────────────────┐
│  [Header]                                                        │
├─────────────────────────────────────────────────────────────────┤
│  ████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   │
│  (Thin bar, 3px, accent color, fills as user scrolls)            │
└─────────────────────────────────────────────────────────────────┘
```

- Height: 3px
- Color: Accent (purple/indigo)
- Width: % of scroll progress (0–100%)
- Smooth updates on scroll

---

## 5. Back-to-Top Button

**Placement:** Fixed bottom-right, above the footer.

**Layout:**
```
                                                    ┌─────┐
                                                    │  ↑  │
                                                    └─────┘
```

- Size: ~48×48px
- Visibility: Only after scrolling ~400px
- Style: Rounded, accent background, white arrow
- Hover: Slight lift and stronger shadow
- Click: Smooth scroll to top
- Optional: Fade in/out when crossing the threshold

---

## 6. Active Navigation State

**Current:** All nav links look the same.

**Proposed:**
- Current page link: Accent color + optional bottom border
- Example: On Accueil, "Accueil" is highlighted

```
Accueil  |  À propos  |  Mentions légales
   ▔▔▔▔
 (accent underline)
```

---

## 7. Skeleton Loaders (Future)

**Placement:** While content is loading (e.g. search or Turbo navigation).

**Layout:**
```
┌─────────────────────────────────────┐
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   │  (Animated gray bars)
│  ░░░░░░░░░░░░░░░░░░░░░░░            │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   │
└─────────────────────────────────────┘
```

- Gray placeholder blocks with a light shimmer
- Replaced by real content when loaded

---

## Implementation Order Suggestion

1. **Theme toggle fix** (priority)
2. **Hero section**
3. **Sun/moon icons**
4. **Active nav state**
5. **Staggered animations**
6. **Reading progress bar**
7. **Back-to-top button**
8. **Skeleton loaders** (when async loading is added)
