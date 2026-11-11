export const contactMethods = ['WhatsApp', 'Call', 'Email'] as const

export const serviceOptions = [
  'Website',
  'Mobile App',
  'ERP System',
  'AI Solution',
  'Branding',
  'UI/UX Design',
  'E-commerce',
  'SEO',
  'Social Media Management',
  'Custom Software',
] as const

export const featuresByService: Record<string, string[]> = {
  Website: [
    'Home',
    'About',
    'Contact',
    'Blog',
    'User Login/Auth',
    'Dashboard',
    'Booking System',
    'Payment Integration',
    'Live Chat',
    'Admin Panel',
    'CMS',
    'Multi-language',
    'Analytics',
    'Push Notifications',
    'Other',
  ],
  'Mobile App': [
    'Onboarding',
    'Auth',
    'Push Notifications',
    'Offline Mode',
    'Camera/Media',
    'Maps',
    'Payment',
    'Social Login',
    'Dark Mode',
    'Admin Panel',
    'Other',
  ],
  'ERP System': [
    'HR Module',
    'Payroll',
    'Inventory',
    'Sales',
    'Purchase',
    'Accounting',
    'CRM',
    'Project Management',
    'Reports',
    'Custom Module',
  ],
  'AI Solution': [
    'Chatbot',
    'Recommendation Engine',
    'Document Intelligence',
    'Data Analytics',
    'Automation',
    'Voice Assistant',
    'Custom AI',
  ],
}

export const designStyles = [
  'Minimal',
  'Luxury',
  'Corporate',
  'Modern',
  'Dark',
  'Colorful',
  'Premium',
] as const

export const budgetRanges = [
  'Under 30,000 ETB',
  '30,000 – 50,000 ETB',
  '50,000 – 100,000 ETB',
  '100,000 – 300,000 ETB',
  '300,000 ETB+',
  'To be discussed',
] as const

export const timelineOptions = [
  'ASAP (within 2 weeks)',
  '1 Month',
  '2 Months',
  '3 Months',
  'Flexible / No Rush',
] as const

export const stepTitles = [
  'Contact Info',
  'Service Needed',
  'Business Info',
  'Project Details',
  'Feature Selection',
  'Design Preferences',
  'Budget Range',
  'Timeline',
  'Additional Notes',
]
