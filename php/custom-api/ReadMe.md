# Custom API

- [Notes](#✅-notes)
- [Introduction](#📘-introduction)
- [Integration](#🧩-integration)
- [Endpoints](#🌐-endpoints)
  - [Health](#health)
  - [Pages](#pages)
  - [Posts](#posts)
  - [Attachments](#attachments)
  - [Taxonomies](#taxonomies)
  - [Menus](#menus)
- [Development](#🧪-development)

## ✅ Notes

- **This project is a work in progress and will need to be updated once it is integrated into the live site as the production site has full data in the database whereas this set up does not**

- These endpoints have Wordpress caching enabled to improve response speed and reduce the load on the server.

- It is important to note some types below as they are used throughout the documents. For more details on the types, please view the [types file](./types.d.ts).

```typescript
type OrderType = "ASC" | "DESC";

type PostOrderBy =
  | "ID"
  | "date"
  | "name"
  | "rand"
  | "title"
  | "author"
  | "modified"
  | "comment_count";

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

type TaxonomyOrderBy = "name" | "slug" | "count" | "term_id" | "id";
```

- If there is no content to return from a successful request, then a custom response will be returned, the HTTP status will be a 200 so that data is returned but the status attribute of the response will be a 204 (the HTTP response for no content).

```jsonc
/* Example: No Content Response */
{
  "data": null,
  "status": 204,
  "error": false,
  "message": "No posts found: {{post_type}}.",
  "meta": [
    /* the pagination meta data */
  ]
}
```

## 📘 Introduction

This document will go over how to integrate the custom api code into an existing Wordpress application.

In addition, this document will also detail endpoints and the expected request and responses for each.

## 🧩 Integration

It is recommended that you save the custom api directory into the `root` of the `theme directory` - this will make it easier to link / import into the `functions.php` file.

With in your themes `functions.php` file, insert the following code into an appropriate place:

```php
  include_once get_template_directory() . '/custom-api/helpers.php';
  foreach (glob(get_template_directory() . '/custom-api/*.php') as $api_file) {
	  if (basename($api_file) !== 'helpers.php') include_once $api_file;
  }
```

## 🌐 Endpoints

The section contains a list of the endpoints available within this custom Wordpress api setup.

**Be aware** that the posts endpoints can be used for any post types, including pages and attachments (Wordpress considers most, if not all, data types as posts), however they have been separated out due to the different use cases / requirements of page and attachment data when bing fetched.

All endpoints are set up to return the response below - this has been done so that the expected responses can be consistent throughout.

```typescript
type ApiResponse = {
  data: any;
  meta?: any;
  error: boolean;
  status: number;
  message: string;
};
```

### Health

This endpoint is here so that it is possible to test if the server and the custom endpoints are available.

```typescript
// GET: {{base_url}}/?rest_route=/hyve/v1/health
```

### Pages

This endpoint collection is used to fetch the Wordpress page data. There are two endpoints here, one for getting a list of page data and the other for getting a single page's data.

#### Page list

Takes the following request data and returns the `ApiResponse` with the `data` being an array of page data objects, and the `meta` data detailing the pagination data.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/pages

// Request:
type PagesRequest = {
  page?: number; // default: 1
  search?: string; // default: ""
  fields?: string[]; // default: undefined (returns all fields)
  per_page?: number; // default: -1
  order?: OrderType; // default: "DESC"
  status?: PostStatus; // default: "publish"
  categories?: string[]; // default: undefined
  order_by?: PostOrderBy; // default: "date"
};
```

#### Page by slug or id

Takes the following request data and returns the `ApiResponse` with the `data` being the page data object, and no `meta` data is returned.

It is important to remember that this endpoint requires at the `id` or the `slug` of the post, otherwise a 400 response will be returned.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/pages/by-slug-id

// Request:
type PageBySlugIdRequest = {
  id?: number; // default: undefined
  slug?: string; // default undefined
  fields?: string[]; // default: undefined (returns all fields)
  post_type?: string; // default: "post"
};
```

### Posts

This endpoint collection is used to fetch the Wordpress post data. There are three endpoints here: one for getting a list of post data; one for listing the different post types available; and the other for getting a single post's data.

#### Post list

Takes the following request data and returns the `ApiResponse` with the `data` being an array of post data objects, and the `meta` data detailing the pagination data.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/posts

// Request:
type PostsRequest = {
  page?: number; // default: 1
  search?: string; // default: ""
  fields?: string[]; // default: undefined (returns all fields)
  per_page?: number; // default: -1
  order?: OrderType; // default: "DESC"
  post_type?: string; // default: "post"
  status?: PostStatus; // default: "publish"
  categories?: string[]; // default: undefined
  order_by?: PostOrderBy; // default: "date"
};
```

#### Post types

Takes the following request data and returns the `ApiResponse` with the `data` being an array of post types with their associated data, and no `meta` data is returned.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/post-types

// Request:
type PostTypesRequest = {
  fields?: string[]; // default: undefined (returns all fields)
};
```

#### Post by slug or id

Takes the following request data and returns the `ApiResponse` with the `data` being the post data object, and no `meta` data is returned.

It is important to remember that this endpoint requires at the `id` or the `slug` of the post, otherwise a 400 response will be returned.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/posts/by-slug-id

// Request:
type PostBySlugIdRequest = {
  id?: number; // default: undefined
  slug?: string; // default undefined
  fields?: string[]; // default: undefined (returns all fields)
  post_type?: string; // default: "post"
};
```

### Attachments

This endpoint collection is used to fetch the Wordpress attachment data. There are two endpoints here, one for getting a list of attachment data, and the other for getting a single attachment's data.

#### Attachment list

Takes the following request data and returns the `ApiResponse` with the `data` being an array of attachment data objects, and the `meta` data detailing the pagination data.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/attachments

// Request:
type AttachmentsRequest = {
  page?: number; // default: 1
  search?: string; // default: ""
  fields?: string[]; // default: undefined (returns all fields)
  per_page?: number; // default: -1
  order?: OrderType; // default: "DESC"
  status?: PostStatus; // default: "inherit"
  order_by?: PostOrderBy; // default: "date"
};
```

#### Attachment by slug or id

Takes the following request data and returns the `ApiResponse` with the `data` being the attachment data object, and no `meta` data is returned.

It is important to remember that this endpoint requires at the `id` or the `slug` of the attachment, otherwise a 400 response will be returned.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/attachments/by-slug-id

// Request:
type AttachmentBySlugIdRequest = {
  id?: number; // default: undefined
  slug?: string; // default undefined
  fields?: string[]; // default: undefined (returns all fields)
};
```

### Taxonomies

This endpoint collection is used to fetch the Wordpress taxonomy data. There are three endpoints here: one for getting a list of taxonomy data; one for listing the different taxonomy types available; and the other for getting a single taxonomy's data.

#### Taxonomy list

Takes the following request data and returns the `ApiResponse` with the `data` being an array of taxonomy data objects, and no `meta` data is returned.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/taxonomies

// Request:
type TaxonomiesRequest = {
  parent?: number; // default: undefined
  search?: string; // default: ""
  fields?: string[]; // default: undefined (returns all fields)
  order?: OrderType; // default: "ASC"
  taxonomy?: string; // default: "category"
  order_by?: TaxonomyOrderBy; // default: "name"
};
```

#### Taxonomy types

Takes the following request data and returns the `ApiResponse` with the `data` being an array of taxonomy types with their associated data, and no `meta` data is returned.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/taxonomy-types

// Request:
type TaxonomyTypesRequest = {
  fields?: string[]; // default: undefined (returns all fields)
};
```

#### Taxonomy by slug or id

Takes the following request data and returns the `ApiResponse` with the `data` being the taxonomy data object, and no `meta` data is returned.

It is important to remember that this endpoint requires at the `id` or the `slug` of the post, otherwise a 400 response will be returned.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/taxonomies/by-slug-id

// Request:
type TaxonomyBySlugIdRequest = {
  id?: number; // default: undefined
  slug?: string; // default: undefined
  fields?: string[]; // default: undefined (returns all fields)
  taxonomy?: string; // default: "category"
};
```

### Menus

This endpoint collection is used to fetch the Wordpress menu data. There are two endpoints here: one for getting the menu options / types, and the other is for getting the items with a single menu.

#### Menu types

Takes no request data and returns the `ApiResponse` with the `data` being an array of menu types with their associated data, and no `meta` data is returned.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/menus
```

#### Menu by type

Takes the following request data and returns the `ApiResponse` with the `data` being the menu item data, and no `meta` data is returned.

It is important to remember that this endpoint requires at the `menu_type`, otherwise a 400 response will be returned.

```typescript
// POST: {{base_url}}/?rest_route=/hyve/v1/menus/by-type

// Request:
type MenuByTypeRequest = {
  fields?: string[]; // default: undefined (returns all fields)
  menu_type: string;
};
```

## 🧪 Development

If updating this repository please keep track of the changes and update this `ReadMe.md` file and any other relative files so that documentation can be kept up-to-date for future developers.
