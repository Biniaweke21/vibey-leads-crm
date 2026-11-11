import {
  Globe,
  Smartphone,
  Building2,
  BrainCircuit,
  Palette,
  PenTool,
  ShoppingCart,
  TrendingUp,
  Share2,
  Code2,
} from '@lucide/vue'
import type { Component } from 'vue'

export interface Service {
  title: string
  description: string
  icon: Component
}

export const services: Service[] = [
  {
    title: 'Website',
    description: 'Fast, responsive websites built to convert visitors into customers.',
    icon: Globe,
  },
  {
    title: 'Mobile App',
    description: 'Native and cross-platform apps for iOS and Android.',
    icon: Smartphone,
  },
  {
    title: 'ERP System',
    description: 'Custom enterprise resource planning systems for your operations.',
    icon: Building2,
  },
  {
    title: 'AI Solution',
    description: 'Practical AI features that automate work and unlock insight.',
    icon: BrainCircuit,
  },
  {
    title: 'Branding',
    description: 'Identity systems that make your business memorable.',
    icon: Palette,
  },
  {
    title: 'UI/UX Design',
    description: 'Interfaces that are clean, intuitive, and on-brand.',
    icon: PenTool,
  },
  {
    title: 'E-commerce',
    description: 'Online stores built to sell, from checkout to fulfillment.',
    icon: ShoppingCart,
  },
  {
    title: 'SEO',
    description: 'Search optimization that gets your business found.',
    icon: TrendingUp,
  },
  {
    title: 'Social Media Management',
    description: 'Consistent, on-brand presence across your social channels.',
    icon: Share2,
  },
  {
    title: 'Custom Software',
    description: 'Bespoke software built around how your business actually works.',
    icon: Code2,
  },
]
