type ApiResponse = {
  data: any;
  meta?: any;
  status: number;
  error: boolean;
  message: string;
};

type AttachmentBySlugIdRequest = {
  id?: number;
  slug?: string;
  fields?: string[];
};

type AttachmentsRequest = {
  page?: number;
  search?: string;
  fields?: string[];
  per_page?: number;
  order?: OrderType;
  status?: PostStatus;
  order_by?: PostOrderBy;
};

type MenuByTypeRequest = {
  fields?: string[];
  menu_type: string;
};

type OrderType = "ASC" | "DESC";

type PageBySlugIdRequest = {
  id?: number;
  slug?: string;
  fields?: string[];
};

type PagesRequest = {
  page?: number;
  search?: string;
  fields?: string[];
  per_page?: number;
  order?: OrderType;
  status?: PostStatus;
  categories?: string[];
  order_by?: PostOrderBy;
};

type PostBySlugIdRequest = {
  id?: number;
  slug?: string;
  fields?: string[];
  post_type?: string;
};

type PostOrderBy =
  | "ID"
  | "date"
  | "name"
  | "rand"
  | "title"
  | "author"
  | "modified"
  | "comment_count";

type PostsRequest = {
  page?: number;
  search?: string;
  fields?: string[];
  per_page?: number;
  order?: OrderType;
  post_type?: string;
  status?: PostStatus;
  categories?: string[];
  order_by?: PostOrderBy;
};

type PostStatus =
  | "any"
  | "draft"
  | "trash"
  | "future"
  | "publish"
  | "private"
  | "pending"
  | "inherit"
  | "auto-draft";

type PostTypesRequest = {
  fields?: string[];
};

type TaxonomiesRequest = {
  parent?: number;
  search?: string;
  fields?: string[];
  order?: OrderType;
  taxonomy?: string;
  order_by?: TaxonomyOrderBy;
};

type TaxonomyBySlugIdRequest = {
  id?: number;
  slug?: string;
  fields?: string[];
  taxonomy?: string;
};

type TaxonomyOrderBy = "name" | "slug" | "count" | "term_id" | "id";

type TaxonomyTypesRequest = {
  fields?: string[];
};
