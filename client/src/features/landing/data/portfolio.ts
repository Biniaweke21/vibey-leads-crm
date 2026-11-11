import {
  Shirt,
  Wallet,
  CreditCard,
  GraduationCap,
  Building2,
  Cpu,
  FileText,
  Coffee,
  Sparkles,
  FlaskConical,
  Tag,
  ScanEye,
} from '@lucide/vue'
import type { Component } from 'vue'

export type ProjectStatus = 'in-development' | 'delivered' | 'live'

export interface Project {
  name: string
  description: string
  category: string
  status: ProjectStatus
  icon: Component
  image?: string
  video?: string
}

export const projects: Project[] = [
  {
    name: 'Libase',
    description: 'AI Wardrobe OS',
    category: 'Fashion Technology',
    status: 'in-development',
    icon: Shirt,
  },
  {
    name: 'Simuni Wallet',
    description: 'AI Money Management',
    category: 'Business OS',
    status: 'in-development',
    icon: Wallet,
    video: '/img/simuni.mp4',
  },
  {
    name: 'Edaye',
    description: 'Smart Retail Credit',
    category: 'Smart Retail',
    status: 'in-development',
    icon: CreditCard,
    image: '/img/Edaye.jpg',
  },
  {
    name: 'AAU IAPAMS',
    description: 'Institutional Platform',
    category: 'Future Infra',
    status: 'delivered',
    icon: GraduationCap,
    image: '/img/AAU.jpg',
  },
  {
    name: 'Alta Computec PLC',
    description: 'Enterprise IT & Business Brilliance',
    category: 'IT Infrastructure',
    status: 'live',
    icon: Building2,
    image: '/img/alta.jpg',
  },
  {
    name: 'Apex Solution',
    description: 'Intelligent Software Engineering',
    category: 'AI Engineering',
    status: 'live',
    icon: Cpu,
    image: '/img/Apex.jpg',
  },
  {
    name: 'AI Resume Analyzer',
    description: 'AI-Powered Career Intelligence Tool',
    category: 'Career Tech',
    status: 'live',
    icon: FileText,
    image: '/img/ai-resume.jpg',
  },
  {
    name: 'Fili Coffee',
    description: 'Ethiopian Coffee Brand & Ordering Experience',
    category: 'Food & Beverage',
    status: 'live',
    icon: Coffee,
    image: '/img/fili-coffee.jpg',
  },
  {
    name: "Tata's Touch",
    description: 'Luxury Nail Salon Booking with an AI Advisor',
    category: 'Booking Platform',
    status: 'live',
    icon: Sparkles,
    image: '/img/tatas-touch.jpg',
  },
  {
    name: 'Takur Perfumes',
    description: 'Luxury Perfume E-commerce Store',
    category: 'E-commerce',
    status: 'live',
    icon: FlaskConical,
    image: '/img/takur-perfumes.jpg',
  },
  {
    name: 'Nexo by Adonay',
    description: 'Personal Brand Identity & Digital Presence',
    category: 'Branding',
    status: 'delivered',
    icon: Tag,
    image: '/img/Nexo.jpg',
  },
  {
    name: 'Omniscan',
    description: 'AI-Powered Scanning & Monitoring Platform',
    category: 'AI Monitoring',
    status: 'in-development',
    icon: ScanEye,
    image: '/img/Omniscan.jpg',
  },
]
