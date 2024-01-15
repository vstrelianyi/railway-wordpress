<?php
/**
 * Plugin Name: Extend GraphQL Media Mutations
 * Description: Extend GraphQL Media Mutations
 * Version: 1.0 | 2023.10.19
 * Author: Viktor Strelianyi | vstr.dev@gmail.com
 * Text Domain: vs-graphql-extend-mutations
*/

function vs_graphql_extend_media_mutations_init() {
	add_action( 'graphql_register_types', function() {
		register_graphql_mutation( 'deleteMediaItems', [
			'inputFields'         => [
				'ids' => [
					'type'        => [ 'list_of' => 'ID' ],
					'description' => 'The IDs of the media items to delete',
				],
			],
			'outputFields'        => [
				'deleted' => [
					'type'        => [ 'list_of' => 'ID' ],
					'description' => 'The IDs of the deleted media items',
				],
			],
			'mutateAndGetPayload' => function( $input ) {
				$deleted = [];
				foreach ( $input['ids'] as $id ) {
					$post = get_post( $id );
					if ( $post && 'attachment' === $post->post_type ) {
						if ( wp_delete_attachment( $id, true ) ) {
							$deleted[] = $id;
						}
					}
			}
				return [
					'deleted' => $deleted,
				];
			},
		] );
	} );
}

add_action( 'init', 'vs_graphql_extend_media_mutations_init' );