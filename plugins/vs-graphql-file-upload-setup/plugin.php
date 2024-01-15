<?php
/**
 * Plugin Name: GraphQL File Upload Setup
 * Description: GraphQL File Upload Setup
 * Version: 1.0 | 2023.10.11
 * Author: Viktor Strelianyi | vstr.dev@gmail.com
 * Text Domain: vs-graphql-file-upload-setup
*/

// https://github.com/dre1080/wp-graphql-upload#usage
// https://github.com/wp-graphql/wp-graphql/issues/311
// https://www.wpgraphql.com/functions/register_graphql_mutation/

function vs_graphql_file_upload_setup() {

	add_action('graphql_register_types', function() {
    register_graphql_mutation(
			'upload', [
				'inputFields' => [
					'files' => [
						'type' => ['non_null' => ['list_of' => 'Upload']],
						'description' => 'The list of files to be uploaded',
					],
					'titles' => [
						'type' => ['list_of' => 'String'],
						'description' => 'Titles of the files',
					],
				],
				'outputFields' => [
					'text' => [
						'type' => 'String',
						'resolve' => function ($payload) {
							return $payload['text'];
						},
					],
					'databaseIds' => [
						'type' => ['list_of' => 'ID'],
						'resolve' => function ($payload) {
							return $payload['databaseIds'];
						},
					],
				],
				'mutateAndGetPayload' => function ($input) {
					if (!function_exists('wp_handle_sideload')) {
						require_once(ABSPATH . 'wp-admin/includes/file.php');
					}

					$databaseIds = [];
					$uploadedFileNames = [];
					$uploadedFileTypes = [];

					foreach ($input['files'] as $index => $file) {
						$file_info = wp_handle_sideload($file, [
							'test_form' => false,
							'test_type' => false,
						]);

						if (isset($file_info['error'])) {
							throw new Exception('File upload error: ' . $file_info['error']);
						}

						$title = isset($input['titles'][$index]) ? $input['titles'][$index] : preg_replace('/\.[^.]+$/', '', $file['name']);

						$attachment = array(
							'post_mime_type' => $file['type'],
							'post_title'     => $title,
							'post_content'   => '',
							'post_status'    => 'inherit',
						);

						$attach_id = wp_insert_attachment($attachment, $file_info['file']);

						require_once(ABSPATH . 'wp-admin/includes/image.php');

						$attach_data = wp_generate_attachment_metadata($attach_id, $file_info['file']);
						wp_update_attachment_metadata($attach_id, $attach_data);

						$databaseIds[] = $attach_id;
						$uploadedFileNames[] = $file['name'];
						$uploadedFileTypes[] = $file['type'];
					}

					$text = 'Uploaded files were ';
					foreach ($uploadedFileNames as $index => $name) {
						$text .= '"' . $name . '" (' . $uploadedFileTypes[$index] . ')';
						if ($index < count($uploadedFileNames) - 1) {
							$text .= ', ';
						}
					}
					$text .= '.';

					return [
						'text' => $text,
						'databaseIds' => $databaseIds,
					];
				}
			]
    );
});

}
add_action( 'init', 'vs_graphql_file_upload_setup' );


