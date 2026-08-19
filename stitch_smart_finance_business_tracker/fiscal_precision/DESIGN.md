---
name: Fiscal Precision
colors:
  surface: '#f8f9fa'
  surface-dim: '#d9dadb'
  surface-bright: '#f8f9fa'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f4f5'
  surface-container: '#edeeef'
  surface-container-high: '#e7e8e9'
  surface-container-highest: '#e1e3e4'
  on-surface: '#191c1d'
  on-surface-variant: '#40493d'
  inverse-surface: '#2e3132'
  inverse-on-surface: '#f0f1f2'
  outline: '#707a6c'
  outline-variant: '#bfcaba'
  surface-tint: '#1b6d24'
  primary: '#0d631b'
  on-primary: '#ffffff'
  primary-container: '#2e7d32'
  on-primary-container: '#cbffc2'
  inverse-primary: '#88d982'
  secondary: '#b6171e'
  on-secondary: '#ffffff'
  secondary-container: '#da3433'
  on-secondary-container: '#fffbff'
  tertiary: '#00569f'
  on-tertiary: '#ffffff'
  tertiary-container: '#006eca'
  on-tertiary-container: '#ebf1ff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#a3f69c'
  primary-fixed-dim: '#88d982'
  on-primary-fixed: '#002204'
  on-primary-fixed-variant: '#005312'
  secondary-fixed: '#ffdad6'
  secondary-fixed-dim: '#ffb3ac'
  on-secondary-fixed: '#410003'
  on-secondary-fixed-variant: '#930010'
  tertiary-fixed: '#d4e3ff'
  tertiary-fixed-dim: '#a5c8ff'
  on-tertiary-fixed: '#001c3a'
  on-tertiary-fixed-variant: '#004786'
  background: '#f8f9fa'
  on-background: '#191c1d'
  surface-variant: '#e1e3e4'
typography:
  headline-xl:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.05em
  numeric-display:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 32px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  unit: 4px
  container-padding: 24px
  gutter: 16px
  card-gap: 20px
  section-margin: 32px
---

## Brand & Style
The design system is anchored in **Corporate / Modern** principles with a focus on high-density information management and absolute clarity. The brand personality is built on the pillars of stability and growth, specifically tailored for financial environments where precision is paramount.

The UI utilizes a **Minimalist** approach with a refined **Tonal Layering** strategy. It avoids unnecessary decoration to ensure that fiscal data remains the focal point. The emotional response should be one of confidence, calm, and organized control. White space is used strategically to separate complex data sets, while subtle animations (e.g., gentle transitions on card hover) provide a sense of modern responsiveness without sacrificing professionalism.

## Colors
The color palette is functional rather than decorative, using semantic associations to guide the user's eye.

- **Primary (#2e7d32):** Represents growth, income, and positive status. Used for "Success" states and primary calls to action.
- **Secondary (#d32f2f):** Represents outflows, debt, and critical alerts. Used for "Danger" states and negative financial values.
- **Accent (#1976d2):** Represents administrative tasks, reporting, and navigation. Used for information-heavy interactions and neutral business logic.
- **Neutral (#f8f9fa):** Provides a soft, low-strain background to allow white content cards to pop with clarity.
- **Status Tints:** For each functional color, a 10% opacity version should be used for background fills in chips or alerts to maintain legibility while preserving the color's meaning.

## Typography
This design system utilizes **Inter** for its exceptional legibility and tabular numeric properties. 

- **Numeric Formatting:** All financial figures must use `font-variant-numeric: tabular-nums` to ensure that columns of numbers align perfectly in tables and dashboards.
- **Currency:** For the Indonesian context, the "Rp" prefix should be treated as a label-style element, often slightly smaller or lighter in weight than the primary figure to emphasize the value itself.
- **Hierarchy:** Use `title-md` for card headers and `label-md` for table headers to create a clear distinction between categories and data.

## Layout & Spacing
The layout follows a **Fluid Grid** model with a 12-column structure for desktop and a single-column stack for mobile.

- **Grid Logic:** Use a 16px gutter between columns. Elements should span increments of 3 or 4 columns for balanced dashboards (e.g., three 4-column cards for top-level metrics).
- **Rhythm:** A 4px baseline grid ensures vertical consistency. Standard spacing between related elements (like a label and an input) is 8px (2 units), while spacing between unrelated sections is 32px (8 units).
- **Safe Areas:** On mobile, page margins should reduce to 16px to maximize the horizontal space for data tables, which may utilize horizontal scrolling with a fixed first column.

## Elevation & Depth
The design system uses **Tonal Layers** and **Ambient Shadows** to define hierarchy.

- **Surface 0:** The main background (`#f8f9fa`). Everything sits on this layer.
- **Surface 1 (Cards):** Pure white (`#ffffff`) with a very soft, diffused shadow: `0px 2px 4px rgba(0, 0, 0, 0.05)`. This is the primary container for data.
- **Surface 2 (Hover/Active):** When a card or element is interacted with, the shadow deepens to `0px 8px 16px rgba(0, 0, 0, 0.08)` to suggest lift.
- **Borders:** Use a 1px solid border (`#e0e2e6`) on cards only if they are placed against a white background; otherwise, rely on the shadow and tonal difference for separation.

## Shapes
The shape language is **Soft** and professional. 

- **Standard Elements:** Buttons, input fields, and small cards use a 4px (0.25rem) radius to maintain a clean, architectural feel.
- **Large Containers:** Dashboard cards and modals use 8px (0.5rem) to soften the overall appearance of the page.
- **Interactive Elements:** Checkboxes use a 2px radius, while radio buttons remain fully circular to ensure standard affordance.

## Components
Consistent implementation of these components ensures a predictable user experience across the dashboard.

- **Financial Cards:** Metric cards should feature a `title-md` header, a `numeric-display` value, and a small "trend" indicator (using Primary Green for up/good or Secondary Red for down/bad).
- **Data Tables:** Use `label-md` for headers with a subtle bottom border. Rows should have a hover state background fill of `#f1f3f5`. Action buttons in rows should be icon-only or ghost buttons to reduce visual noise.
- **Floating Label Inputs:** Input fields should use a 1px border. On focus, the border color changes to Accent Blue (#1976d2) and the label shrinks and floats to the top-left of the border.
- **Dynamic List Inputs:** For adding multiple transaction items, use a "row-based" approach with a trailing "Delete" icon button. Provide an "Add Item" button at the bottom using a Ghost/Tertiary style.
- **Charts:** Use thin 2px lines for line charts with a 10% opacity fill underneath the line. Colors should strictly follow the Primary/Secondary/Accent palette to ensure semantic meaning (e.g., Green line for Income).
- **Localization:** All currency displays must follow Indonesian formatting (e.g., **Rp 1.250.000,00**). Dates should follow the DD/MM/YYYY format.