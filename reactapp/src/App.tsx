import { createBrowserRouter } from 'react-router'
import Layout from './layouts/Layout'
import Page1 from './pages/Page1'
import Page2 from './pages/Page2'

const router = createBrowserRouter([
  {
    path: '/prueba',
    Component: Layout,
    children: [
      { path: 'page1', Component: Page1 },
      { path: 'page2', Component: Page2 },
    ],
  },
])

export default router
