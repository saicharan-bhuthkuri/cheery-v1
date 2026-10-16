# KaamMitra - On-Demand Professional Home Services Marketplace (Cheery v1)

A modern, high-conversion, responsive public landing page showcasing on-demand home maintenance services with bespoke branding, standardized transparent rate cards, customer testimonials, and 24/7 helpline contact.

---

## 🌟 Key Features

- **Modern Marketplace Showcase**:
  - Quick Category Hubs with instant service filtering and micro-interactions.
  - Curated Hero Bento grid with verified guarantee badges and live pro spotlight.
  - Interactive Live Search with instantaneous auto-suggestions and catalog filtering.
  - Transparent ₹149 Doorstep Visiting Fee pledge with 30-Day Guarantee and Zero Advance requirement.
  - Complete Services Catalog with photographic cards, inspection checklists, and expandable rate cards.
  - The KaamMitra Standard: 4 trust pillars covering Aadhaar/Police verification, fixed pricing, and 30-day free rework.
  - Step-by-step "How It Works" pipeline.
  - Verified Customer Testimonials with aggregate trust metrics (4.88★ rating, 100% verified).
  - Customer FAQs and dedicated 24/7 Helpline & Support contact desk.
- **Unique Photography Assets**:
  - 13 high-resolution, distinct service photography assets (no placeholders).
  - High visual fidelity with crisp aspect ratios and lazy loading.
- **Responsive & Accessible**:
  - Adaptive desktop, tablet, and mobile drawer navigation with mobile bottom navigation bar.
  - Native dark mode support with CSS custom property palette.
  - Fast page loads with vanilla CSS architecture and zero heavy front-end framework bloat.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.x (Native, zero dependencies)
- **Frontend**: HTML5, Vanilla CSS3 (Custom Design System, Variables, CSS Grid & Flexbox)
- **Icons**: Lucide Icons & Custom SVG Icon Renderer
- **Typography**: Plus Jakarta Sans & Outfit (Google Fonts)

---

## 🚀 Getting Started

### Prerequisites

- PHP 7.4+ or PHP 8.x installed on your machine.
- Web browser (Chrome, Edge, Firefox, Safari).

### Local Development

1. Clone or navigate to the repository:
   ```bash
   cd cheery-v1
   ```

2. Start the built-in PHP development server:
   ```bash
   php -S localhost:8080 -t public
   ```
   *(or run `php -S localhost:8080` and navigate to `http://localhost:8080/public/index.php`)*

3. Open your browser and navigate to:
   ```
   http://localhost:8080/
   ```

---

## 📂 Project Structure

```
cheery-v1/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── dark-mode.css        # Dark theme color overrides
│   │   │   ├── style.css            # Primary marketplace stylesheet
│   │   │   └── variables.css        # CSS custom properties & design tokens
│   │   ├── images/                  # 13 high-definition service photos
│   │   └── js/
│   │       ├── lucide.min.js        # Lucide icon library
│   │       └── main.js              # Header, search, drawer, filters & FAQ controller
│   ├── components/
│   │   ├── footer-main.php          # Public marketplace footer & city modal
│   │   ├── icons.php                # Unified SVG / Lucide icon renderer
│   │   └── navbar-main.php          # Floating island navbar & mobile drawer
│   ├── data/
│   │   ├── faqs.php                 # Frequently asked questions dataset
│   │   ├── features.php             # Trust badges & platform statistics
│   │   ├── services.php             # Full services catalog & rate cards
│   │   └── testimonials.php         # Customer reviews dataset
│   ├── includes/
│   │   └── config.php               # Configuration forwarder
│   ├── config.php                   # Global application configuration
│   └── index.php                    # Main public landing page
├── .gitignore                       # Git ignore file
└── README.md                        # Project documentation
```

---

## 📄 License

This project is licensed under the MIT License - feel free to customize and build upon it!
