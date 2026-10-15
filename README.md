# KaamMitra - On-Demand Professional Home Services Marketplace (Cheery v1)

A modern, high-conversion, responsive home services marketplace web platform inspired by Urban Company's browsing experience with bespoke branding and interactive booking flows.

---

## 🌟 Key Features

- **Urban Company-Inspired UX**:
  - 8-tile Quick Category shortcuts with dynamic micro-interactions.
  - Curated 2x2 photo hero showcase with verified guarantee badges.
  - "In the Spotlight" promotional cards with photography thumbnails.
  - "New & Noteworthy" modern service grid.
  - "Most Booked Services" horizontal showcase.
  - Category-specific carousels: Cleaning Essentials, Home Repair & Maintenance, and Appliance Repair & Service.
  - High-impact promotional split lifestyle banners with rapid dispatch pills.
- **Unique Photography Assets**:
  - 13 high-resolution, distinct service photography assets (no repetitive or cloned placeholders).
  - High visual fidelity with crisp aspect ratios and lazy loading.
- **Interactive Booking Flow**:
  - Multi-step modal booking with category selection, date/time scheduling, and real-time validation.
  - Asynchronous AJAX booking submission (`api/submit-booking.php`) with simulated technician assignment and tracking ID generation.
- **Service Partner Onboarding**:
  - Dedicated partner registration modal with Aadhaar and skill verification workflow.
- **Responsive & Accessible**:
  - Adaptive desktop, tablet, and mobile drawer navigation with sticky bottom action bar.
  - Dark mode support with persistent user preferences.
  - Fast page loads with vanilla CSS architecture and zero heavy front-end framework bloat.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.x (Native, zero dependencies)
- **Frontend**: HTML5, Vanilla CSS3 (Custom Design System, Variables, CSS Grid & Flexbox)
- **Icons**: FontAwesome 6 Pro CDN + SVG Icons
- **Typography**: Plus Jakarta Sans & Outfit (Google Fonts)
- **API**: RESTful JSON endpoints (`/api/submit-booking.php`)

---

## 🚀 Getting Started

### Prerequisites

- PHP 7.4+ or PHP 8.x installed on your machine.
- Web browser (Chrome, Edge, Firefox, Safari).

### Local Development

1. Clone the repository:
   ```bash
   git clone https://github.com/saicharan-bhuthkuri/cheery-v1.git
   cd cheery-v1
   ```

2. Start the built-in PHP development server:
   ```bash
   php -S localhost:8080
   ```

3. Open your browser and navigate to:
   ```
   http://localhost:8080/index.php
   ```

---

## 📂 Project Structure

```
├── api/
│   └── submit-booking.php       # Booking submission JSON endpoint
├── assets/
│   ├── css/
│   │   ├── dark-mode.css        # Dark theme color overrides
│   │   ├── style.css            # Primary marketplace stylesheet
│   │   └── variables.css        # CSS custom properties & design tokens
│   ├── images/                  # 13 high-definition service photos
│   └── js/
│       ├── booking.js           # Multi-step booking modal controller
│       ├── main.js              # Header, search, drawer & FAQ controller
│       └── partner.js           # Partner registration controller
├── data/
│   ├── faqs.php                 # Frequently asked questions dataset
│   ├── features.php             # Trust badges & platform statistics
│   ├── services.php             # Full services catalog & rate cards
│   └── testimonials.php         # Customer reviews dataset
├── .gitignore                   # Git ignore file
├── config.php                   # Global application configuration
├── index.php                    # Main landing page
└── README.md                    # Project documentation
```

---

## 📄 License

This project is licensed under the MIT License - feel free to customize and build upon it!
