Tech Task Description

Instructions:
 - Clone the git repository (git clone git@github.com:Hayk-web/technical-task.git)
 - Run the following commands:
   - lando start
   - lando composer install
   - lando drush site:install --db-url=mysql://drupal10:drupal10@database/drupal10 -y
   - lando drush cr


The Tech task content filter module provides:
1.	A custom content type (custom_content) with fields:
  •	Title: Standard Drupal title field.
  •	Image: Image field for uploading a visual representation.
  •	Description: Long text field for detailed content.
  •	Tags: Taxonomy term reference field tied to a vocabulary (tags).

2.	A Controller that allows filtering through content of this type based on:
  • Tags: Filtered using taxonomy term names.
  •	Title: Partial match using a LIKE query.
  •	Returns a JSON response with content details.
3.	A Block Plugin that displays the 3 most recent items of this content type in a visually appealing card layout using a custom Twig template and CSS styling.
   For styling another solution works better and matching to best practices: It's adding styling via for example SCSS and use compiler to compile scss to css.


Features

Content Type
 •	Automatically created during module installation.
 •	Fields:
   •	Title: A standard Drupal title field.
   •	Image: A single-image upload field.
   •	Description: A long text field for descriptions.
   •	Tags: A reference to taxonomy terms in the tags vocabulary.

Controller
 •	URL: /custom-content/filter
 •	Accepts query parameters:
   •	title: Filter content by title (partial match).
   •	tag: Filter content by tag names (comma-separated).
   •	Supports OR logic for title and tags.
 •	Returns content data as a JSON response:
   •	id: Node ID.
   •	title: Content title.
   •	description: Content description.
   •	image: Fully qualified URL of the image.
   •	tags: Comma-separated list of tag names.

Block Plugin
  •	Displays the 3 most recent custom_content items as cards.
  •	Includes a custom Twig template (templates/custom-content-block.html.twig).
  •	Styled using CSS/Sass to achieve a modern card-based layout.
  •	Automatically available under Structure > Block layout.


Performance Considerations
  •	The API Controller utilizes cache contexts and tags for efficient caching and invalidation.
  •	Block content is also cached to avoid repetitive database queries.
  •	NOTE: Images rendering part should be modified, should be created the Image style and render an image using that style.
