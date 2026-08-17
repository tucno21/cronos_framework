import { api } from '../lib/api'

export interface Blog {
  id: number
  user_id: number
  title: string
  slug: string
  content: string
  name?: string
  created_at?: string
  updated_at?: string
}

export const getBlogs = async (): Promise<Blog[]> => {
  const { data } = await api.get('blogs')
  return data.blogs ?? []
}

export const getBlog = async (slug: string): Promise<Blog | null> => {
  const { data } = await api.get(`blogs/${slug}`)
  return data.blog ?? null
}

export const createBlog = async (payload: Pick<Blog, 'title' | 'slug' | 'content'>) => {
  const { data } = await api.post('blogs', payload)
  return data
}

export const updateBlog = async (id: number, payload: Pick<Blog, 'title' | 'slug' | 'content'>) => {
  const { data } = await api.put(`blogs/${id}`, payload)
  return data
}

export const deleteBlog = async (id: number) => {
  const { data } = await api.delete(`blogs/${id}`)
  return data
}
