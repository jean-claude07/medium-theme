# MediumClone - WordPress Theme

A modern, minimalist WordPress theme inspired by Medium and Substack, built for high-performance publishing and social engagement.

![Design Preview](assets/screenshot.png) <!-- Note: Replace with actual screenshot path if available -->

## ✨ Features

- **Premium UI/UX**: Clean typography, generous whitespace, and a minimalist aesthetic.
- **Modern Single Post Layout**:
  - **Reading Progress Bar**: Visual indicator of scroll progress.
  - **Floating Sidebar**: Compact pill for quick reactions (Clap, Love), comments, and bookmarks.
  - **Refined Author Cards**: Visually rich author profiles with follower counts.
- **Advanced Comment System**: Side-panel responses with a clean, flat design and threaded conversations.
- **Gamification Engine**: Integrated badge and points system to drive user engagement.
- **Social Interaction**: Built-in AJAX-powered reactions and bookmarking features.
- **Frontend Management**: Complete dashboard for profile editing and post management.
- **Inline Image Cropping**: Smooth, non-disruptive avatar editing using Cropper.js.
- **SEO Optimized**: Built-in logic for meta tags, descriptive titles, and optimized headings.
- **Dark Mode Support**: Seamless transition between light and dark themes.

## 🛠️ Tech Stack

- **Framework**: [WordPress](https://wordpress.org/)
- **Styling**: [Tailwind CSS](https://tailwindcss.com/)
- **Reactivity**: [Alpine.js](https://alpinejs.dev/)
- **Animations**: [GSAP](https://greensock.com/gsap/)
- **Components**: [Swiper.js](https://swiperjs.com/)
- **Image Processing**: [Cropper.js](https://fengyuanchen.github.io/cropperjs/)

## 🚀 Installation & Setup

### 1. Prerequisites
Ensure you have **Node.js** and **npm** installed on your development machine.

### 2. Dependencies
Clone the theme into your `wp-content/themes/` directory and install the required packages:

```bash
cd wp-content/themes/theme-medium-clone
npm install
```

### 3. Development
To watch for CSS changes and rebuild the Tailwind stylesheet in real-time:

```bash
npm run dev
```

### 4. Production Build
To generate a minified and optimized stylesheet for production:

```bash
npm run build
```

## 📂 Project Structure

- `inc/` - Core PHP functionality (SEO, Gamification, Reactions, etc.).
- `assets/` - Source CSS, JS files, and images.
- `template-parts/` - Reusable theme components.
- `single.php` - The primary post template.
- `comments.php` - The redesigned comment system.
- `page-dashboard.php` - Frontend user dashboard.

## 📄 License

This project is licensed under the ISC License.
# medium-theme
# medium-theme
