/**
 * Homepage nav is the source of truth for every page: Home, Services,
 * Quote Builder, Resources, Contact, and the Get A Quote action.
 */

export interface NavLink {
  label: string;
  href: string;
  description?: string;
  /** astro-icon name, e.g. 'lucide:wrench'. */
  icon?: string;
}

export interface MegaColumn {
  heading?: string;
  links: NavLink[];
}

export interface MegaPanel {
  kind: 'mega';
  columns: MegaColumn[];
  featured?: {
    title: string;
    body: string;
    href: string;
    cta: string;
  };
}

export interface LinkListPanel {
  kind: 'links';
  links: NavLink[];
}

export interface NavItem {
  label: string;
  /** Present when the top-level item is itself a destination. */
  href?: string;
  panel?: MegaPanel | LinkListPanel;
}

export interface NavigationConfig {
  primary: NavItem[];
  /** Right-hand call to action in the header. */
  cta?: { label: string; href: string };
  footer: { heading: string; links: NavLink[] }[];
  legal: NavLink[];
}

export const navigation: NavigationConfig = {
  primary: [
    { label: 'Home', href: '/' },
    { label: 'Services', href: '/services/' },
    { label: 'Quote Builder', href: '/quote/' },
    { label: 'Resources', href: '/resources/' },
    { label: 'Contact', href: '/contact/' },
  ],

  cta: { label: 'Get A Quote', href: '/quote/' },

  footer: [
    {
      heading: 'Sitemap',
      links: [
        { label: 'Global Network', href: '/services/' },
        { label: 'Warehouse Solutions', href: '/services/' },
        { label: 'Carrier Partnerships', href: '/partners/' },
        { label: 'Compliance', href: '/services/' },
      ],
    },
    {
      heading: 'Resources',
      links: [
        { label: 'Client Portal', href: '/contact/' },
        { label: 'Whitepapers', href: '/resources/' },
        { label: 'Newsroom', href: '/resources/' },
      ],
    },
  ],

  legal: [
    { label: 'Privacy Policy', href: '/privacy/' },
    { label: 'Terms of Service', href: '/terms/' },
    { label: 'Cookie Policy', href: '/cookie/' },
  ],
};
