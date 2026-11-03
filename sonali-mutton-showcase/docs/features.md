# 🌟 Detailed Feature Specifications

This document outlines the core functional modules developed for **Sonali Mutton Shop**.

---

## 1. Customer Storefront

- **Hero Landing Page:** Features shop branding, promotional highlights, daily specials, customer testimonials, and direct WhatsApp contact buttons.
- **Product Catalog:**
  - Filtering by category (Fresh Mutton, Kheema, Chops, Bones, Organ Meats, Special Cuts).
  - Instant text search matching product names, Marathi aliases, and descriptions.
  - Custom unit pricing (per kg, per 500g, per piece).
  - In-stock and out-of-stock real-time status indicators.
- **Bilingual Switcher:** Single-click toggle between English and Marathi across every page with state persistence across user sessions.
- **Contact & Inquiry Submission:** Interactive contact form with input validation and customer review submission.

---

## 2. Cart & Order Management

- **Interactive Shopping Cart:** Real-time quantity adjustments, subtotal recalculation, and dynamic delivery fee estimation.
- **Checkout Process:**
  - Support for registered customers and guest checkout.
  - Delivery address collection with PIN code verification.
  - Unique human-readable order identifier generation (e.g. `SMS-20261103-A1F3`).
  - Cash on Delivery (COD) and digital payment readiness.
- **Order Tracking:** Real-time status progression (`pending` → `confirmed` → `preparing` → `out_for_delivery` → `delivered`).

---

## 3. Customer Account Portal

- **User Authentication:** Secure registration and login protected with `password_hash()`.
- **Order History:** Detailed view of past purchases, downloaded invoices, and itemized receipts.
- **Profile Management:** Saved addresses and contact details for rapid one-click re-ordering.

---

## 4. Administrative Control Suite

- **Executive Dashboard:** Live statistics showing daily sales revenue, active orders, total registered customers, and low-stock alerts.
- **Product & Category CRUD:** Add, update, hide, or delete products with dual Marathi/English fields and thumbnail upload management.
- **Order Processing:** Update order fulfillment stages, customer contact verification, and export summaries.
- **Store Configuration:** Dynamic shop timings, contact numbers, banner notices, and delivery charge thresholds editable without code modification.
