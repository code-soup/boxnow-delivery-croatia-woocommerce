---
name: custom-post-type
description: Register custom post types and taxonomies with proper labels, capabilities, and features. Use when creating custom content types like portfolios, testimonials, or products.
---

# Custom Post Type

Register custom post types and taxonomies.

## Create CPT Class

**File**: `includes/post-types/class-portfolio.php`

```php
<?php

namespace CodeSoup\BoxNow\PostTypes;

use CodeSoup\BoxNow\Core\Hooker;

defined( 'ABSPATH' ) || exit;

class Portfolio {

	private Hooker $hooker;

	public function __construct( Hooker $hooker ) {
		$this->hooker = $hooker;
	}

	public function init(): void {
		$this->hooker->add_action(
			'init',
			$this,
			'register_post_type'
		);

		$this->hooker->add_action(
			'init',
			$this,
			'register_taxonomy'
		);
	}

	public function register_post_type(): void {
		$labels = array(
			'name'          => __( 'Portfolio', 'codesoup-woo-boxnow' ),
			'singular_name' => __( 'Portfolio Item', 'codesoup-woo-boxnow' ),
			'add_new'       => __( 'Add New', 'codesoup-woo-boxnow' ),
			'add_new_item'  => __( 'Add New Item', 'codesoup-woo-boxnow' ),
			'edit_item'     => __( 'Edit Item', 'codesoup-woo-boxnow' ),
			'new_item'      => __( 'New Item', 'codesoup-woo-boxnow' ),
			'view_item'     => __( 'View Item', 'codesoup-woo-boxnow' ),
			'search_items'  => __( 'Search Items', 'codesoup-woo-boxnow' ),
			'not_found'     => __( 'No items found', 'codesoup-woo-boxnow' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'has_archive'         => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-portfolio',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'rewrite'             => array( 'slug' => 'portfolio' ),
			'capability_type'     => 'post',
			'hierarchical'        => false,
		);

		register_post_type( 'csbxwoo_portfolio', $args );
	}

	public function register_taxonomy(): void {
		$labels = array(
			'name'          => __( 'Categories', 'codesoup-woo-boxnow' ),
			'singular_name' => __( 'Category', 'codesoup-woo-boxnow' ),
			'search_items'  => __( 'Search Categories', 'codesoup-woo-boxnow' ),
			'all_items'     => __( 'All Categories', 'codesoup-woo-boxnow' ),
			'edit_item'     => __( 'Edit Category', 'codesoup-woo-boxnow' ),
			'update_item'   => __( 'Update Category', 'codesoup-woo-boxnow' ),
			'add_new_item'  => __( 'Add New Category', 'codesoup-woo-boxnow' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'portfolio-category' ),
		);

		register_taxonomy( 'csbxwoo_portfolio_cat', array( 'csbxwoo_portfolio' ), $args );
	}
}
```

## Common Arguments

### Post Type

```php
array(
	'public'             => true,           // Public visibility
	'has_archive'        => true,           // Archive page
	'show_in_rest'       => true,           // Gutenberg support
	'menu_icon'          => 'dashicons-*',  // Admin menu icon
	'supports'           => array(          // Features
		'title',
		'editor',
		'thumbnail',
		'excerpt',
		'custom-fields',
	),
	'rewrite'            => array(
		'slug' => 'custom-slug',
	),
	'capability_type'    => 'post',         // Capabilities
	'hierarchical'       => false,          // Like pages (true) or posts (false)
)
```

### Taxonomy

```php
array(
	'hierarchical'      => true,   // Like categories (true) or tags (false)
	'public'            => true,
	'show_ui'           => true,
	'show_admin_column' => true,   // Show in admin list
	'show_in_rest'      => true,   // Gutenberg support
	'rewrite'           => array(
		'slug' => 'custom-tax',
	),
)
```

## Meta Boxes

```php
public function init(): void {
	$this->hooker->add_actions(
		$this,
		array(
			'init',
			'add_meta_boxes',
			'save_post',
		)
	);
}

public function add_meta_boxes(): void {
	add_meta_box(
		'csbxwoo_portfolio_details',
		__( 'Portfolio Details', 'codesoup-woo-boxnow' ),
		array( $this, 'render_meta_box' ),
		'csbxwoo_portfolio',
		'normal',
		'default'
	);
}

public function render_meta_box( $post ): void {
	wp_nonce_field( 'csbxwoo_portfolio_meta', 'csbxwoo_portfolio_nonce' );

	$url = get_post_meta( $post->ID, '_portfolio_url', true );
	?>
	<label><?php esc_html_e( 'URL:', 'codesoup-woo-boxnow' ); ?></label>
	<input type="text" name="portfolio_url" value="<?php echo esc_attr( $url ); ?>" />
	<?php
}

public function save_post( $post_id ): void {
	if ( ! isset( $_POST['csbxwoo_portfolio_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['csbxwoo_portfolio_nonce'], 'csbxwoo_portfolio_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['portfolio_url'] ) ) {
		update_post_meta(
			$post_id,
			'_portfolio_url',
			sanitize_url( $_POST['portfolio_url'] )
		);
	}
}
```

## Custom Columns

```php
public function init(): void {
	$this->hooker->add_filter(
		'manage_csbxwoo_portfolio_posts_columns',
		$this,
		'add_columns'
	);

	$this->hooker->add_action(
		'manage_csbxwoo_portfolio_posts_custom_column',
		$this,
		'render_column',
		10,
		2
	);
}

public function add_columns( array $columns ): array {
	$columns['portfolio_url'] = __( 'URL', 'codesoup-woo-boxnow' );
	return $columns;
}

public function render_column( string $column, int $post_id ): void {
	if ( 'portfolio_url' === $column ) {
		$url = get_post_meta( $post_id, '_portfolio_url', true );
		echo esc_html( $url );
	}
}
```

## Register in Service Provider

```php
<?php

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\PostTypes\Portfolio;

class PostTypeServiceProvider extends AbstractServiceProvider {

	public function register(): void {
		$this->singleton(
			'post_types.portfolio',
			Portfolio::class
		);
	}

	public function boot(): void {
		$this->container->get( 'post_types.portfolio' )->init();
	}
}
```

## Flush Rewrite Rules

After registering CPT, flush rewrite rules once:

```php
// In Activator class
public static function activate(): void {
	// Register CPT
	$portfolio = new Portfolio( new Hooker() );
	$portfolio->register_post_type();

	// Flush
	flush_rewrite_rules();
}
```

## Rules

- Create in `includes/post-types/`
- Use plugin prefix in post type slug
- Register on `init` hook
- Use Hooker service
- Make strings translatable
- Set `show_in_rest` for Gutenberg
- Flush rewrite rules on activation
- Use proper capability checks

