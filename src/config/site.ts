/**
 * Per-project configuration. This and `navigation.ts` are the two files that
 * must be filled in for every new site. Blank optional values ship nothing —
 * an empty analytics ID means that vendor's script is never emitted.
 */

export interface AnalyticsConfig {
  /** LogDash website ID (`data-website-id`). Blank skips the script. */
  logdash: string;
  ga4: string;
  gtm: string;
  metaPixel: string;
  bingUet: string;
  clarity: string;
}

export type SchemaBusinessType =
  | 'LocalBusiness'
  | 'ProfessionalService'
  | 'HomeAndConstructionBusiness'
  | 'Plumber'
  | 'Electrician'
  | 'RoofingContractor'
  | 'GeneralContractor'
  | 'Dentist'
  | 'Physician'
  | 'Attorney'
  | 'AccountingService'
  | 'InsuranceAgency'
  | 'RealEstateAgent';

export interface SiteConfig {
  /** Absolute origin, no trailing slash. Must match `site` in astro.config.mjs. */
  url: string;
  name: string;
  legalName?: string;
  tagline: string;
  description: string;
  locale: string;

  business: {
    schemaType: SchemaBusinessType;
    phone: string;
    /** Digits only, E.164 — used for tel: links. */
    phoneHref: string;
    email: string;
    supportEmail?: string;
    address: {
      street: string;
      /** Second street line, such as a street address when `street` is a P.O. box. */
      line2?: string;
      locality: string;
      region: string;
      postalCode: string;
      country: string;
    };
    /** Omit entirely for service-area businesses with no walk-in location. */
    geo?: { latitude: number; longitude: number };
    /** schema.org openingHours strings, e.g. 'Mo-Fr 08:00-17:00'. */
    hours: string[];
    priceRange?: string;
  };

  social: {
    facebook?: string;
    instagram?: string;
    linkedin?: string;
    x?: string;
    youtube?: string;
    tiktok?: string;
  };

  /** Absolute or site-relative path to the fallback Open Graph image. */
  defaultOgImage: string;

  /** Relative path to the PHP form handler. Blank disables all forms. */
  formEndpoint: string;

  /**
   * Google reCAPTCHA Enterprise site key (public). Blank skips the widget.
   * The project ID and API key are configured server-side in ~/private/site-mail.php.
   */
  recaptchaSiteKey: string;

  analytics: AnalyticsConfig;

  verification: {
    google: string;
    bing: string;
    meta: string;
  };

  /** 'none' is correct for US-only clients. Switch to 'banner' only when required. */
  consent: 'none' | 'banner';
}

export const site: SiteConfig = {
  url: (import.meta.env.SITE || 'https://www.hbdistributing.com').replace(/\/$/, ''),
  name: 'Highland Breeze Distributing',
  legalName: 'Highland Breeze Distributing, Inc.',
  tagline: 'Industrial Products. Delivered Dependably.',
  description:
    'Highland Breeze Distributing, Inc. provides industrial product distribution, sourcing, and supply chain support for manufacturers, OEMs, maintenance teams, and industrial customers. We help customers simplify purchasing, locate reliable products, and keep operations moving.',
  locale: 'en-US',

  business: {
    schemaType: 'LocalBusiness',
    phone: '+1 (704) 282-2366',
    phoneHref: '+17042822366',
    /** General inquiries, corporate office, and the contact form. */
    email: 'info@hbdistributing.com',
    /** Published support address. Owner and personal mailboxes stay off the site. */
    supportEmail: 'support@hbdistributing.com',
    address: {
      street: 'P.O. Box 129',
      line2: '310 Marion Ave.',
      locality: 'Summerville',
      region: 'SC',
      postalCode: '29483',
      country: 'US',
    },
    hours: ['Mo-Fr 08:00-18:00'],
  },

  social: {},

  defaultOgImage: '/og-default.png',

  formEndpoint: '/api/submit.php',
  recaptchaSiteKey: '6LeSt8stAAAAAECAC7v5zq8xauIXSMiBBa5gn3A5',

  analytics: {
    logdash: '',
    ga4: '',
    gtm: '',
    metaPixel: '',
    bingUet: '',
    clarity: '',
  },

  verification: {
    google: '',
    bing: '',
    meta: '',
  },

  consent: 'none',
};

export const formattedAddress = [
  site.business.address.street,
  site.business.address.line2,
  `${site.business.address.locality}, ${site.business.address.region} ${site.business.address.postalCode}`,
]
  .filter(Boolean)
  .join(', ');

/** LogDash site ID for LogDash.astro (`data-website-id`). Blank skips the script. */
export const logdashWebsiteId = site.analytics.logdash;

/** No configured third-party ID means the ad-tag bundle is never mounted. */
export const hasAnalytics = Object.entries(site.analytics).some(
  ([key, value]) => key !== 'logdash' && Boolean(value),
);

/** Production stays out of search results until ALLOW_INDEXING=true at build time. */
export const allowIndexing = import.meta.env.PUBLIC_ALLOW_INDEXING === 'true';
