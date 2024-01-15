<?php
/**
 * Plugin Name: Extend GraphQL mutations with ACF fields
 * Description: Extend GraphQL mutations with ACF fields
 * Version: 1.1 | 2023.11.28
 * Author: Viktor Strelianyi | vstr.dev@gmail.com
 * Text Domain: vs-extend-graphql-mutations
*/


// https://github.com/wp-graphql/wp-graphql/issues/214
// https://github.com/wp-graphql/wp-graphql/issues/957
// https://github.com/wp-graphql/wp-graphql-acf/issues/11#issuecomment-773925428

// https://www.wpgraphql.com/actions/

function removeItemFromArray($array, $itemToRemove) {
	// Use array_search to find the item's index
	$index = array_search($itemToRemove, $array);

	// Check if the item was found in the array
	if ($index !== false) {
			// Use unset to remove the item by index
			unset($array[$index]);
	}

	// Re-index the array to fill any gaps
	$array = array_values($array);

	return $array;
}

function getUniqueArray($inputArray) {
	$uniqueArray = array_unique($inputArray);
	$uniqueArray = array_values($uniqueArray);

	return $uniqueArray;
}

function update_media_title( $attachment_id, $new_title ) {
	$my_post = array(
		'ID'           => $attachment_id,
		'post_title'   => $new_title,
	);
	wp_update_post( $my_post );
}

function vs_extend_graphql_mutations_init() {

	// REGISTER CUSTOM TYPES
	add_action('graphql_register_types', function () {

		register_graphql_input_type('PackSizeInput', [
			'fields' => [
				'pack_size_value' => [
					'type' => 'String',
					'description' => 'Value of the pack size',
				],
				'pack_size_image' => [
					'type' => 'ID',
					'description' => 'ID of the pack size image',
				],
				// 'pack_size_name' => [
				// 	'type' => 'String',
				// 	'description' => 'Name of the pack size',
				// ],
			],
		]);

    register_graphql_input_type('ProductSizeInput', [
			'fields' => [
				'prev_product_size_value' => [
					'type' => 'String',
					'description' => 'Previous value of the product size',
				],
				'product_size_value' => [
					'type' => 'String',
					'description' => 'Value of the product size',
				],
				'product_size_image' => [
					'type' => 'ID',
					'description' => 'ID of the product size image',
				],
				'with_pack_sizes' => [
					'type' => 'Boolean',
					'description' => 'true if product should have pack sizes',
				],

				// 'nutrition' => [
				// 	'type' => 'String',
				// 	'description' => 'Ingredients of the product size',
				// ],

				// 'batch_number' => [
				// 	'type' => 'String',
				// 	'description' => 'Batch number for product size',
				// ],
				// 'barcode' => [
				// 	'type' => 'String',
				// 	'description' => 'Barcode for product size',
				// ],
				// 'use_by_date' => [
				// 	'type' => 'String',
				// 	'description' => 'Use by date for product size',
				// ],
				// 'packaging_type' => [
				// 	'type' => 'String',
				// 	'description' => 'Packaging type for product size',
				// ],
				// 'packaging_material' => [
				// 	'type' => 'String',
				// 	'description' => 'Packaging material for product size',
				// ],
				// 'recyclable' => [
				// 	'type' => 'String',
				// 	'description' => 'Recyclable for product size',
				// ],
				// 'recycle_number' => [
				// 	'type' => 'String',
				// 	'description' => 'Recycle number for product size',
				// ],
				// 'recycled_material' => [
				// 	'type' => 'String',
				// 	'description' => 'Recycled material for product size',
				// ],
				// 'recycled_material_percentage' => [
				// 	'type' => 'String',
				// 	'description' => 'Recycled material percentage for product size',
				// ],
				// 'country' => [
				// 	'type' => 'String',
				// 	'description' => 'Country for product size',
				// ],
				// 'collaboration_type' => [
				// 	'type' => 'String',
				// 	'description' => 'Collaboration type for product size',
				// ],
				// 'collaboration_with' => [
				// 	'type' => 'String',
				// 	'description' => 'Collaboration with for product size',
				// ],

				// 'ingredients' => [
				// 	'type' => 'String',
				// 	'description' => 'Ingredients of the product size',
				// ],

				'pack_sizes' => [
					'type' => [
						'list_of' => 'PackSizeInput', // Define the nested input type
					],
					'description' => 'List of pack sizes',
				],

				// 'product_size_name' => [
				// 	'type' => 'String',
				// 	'description' => 'Name of the product size',
				// ],

				// 'product_size_unit' => [
				// 	'type' => 'String',
				// 	'description' => 'Unit of the product size',
				// ],
			],
    ]);
	});

	// ON CPT DELETE
	add_action(
		'before_delete_post',
		function ( $post_id, $post ) {

			if ( $post->post_type === 'companies' ) {
				$company_id = intval( $post_id );
				// in owners update companies array
				$ownersIdsArray =  get_post_meta( $company_id, 'owner-company', true) ?: [];
				foreach ( $ownersIdsArray as $owner_id ) {
					$companiesIdsArray = get_post_meta( $owner_id, 'owner-company', true ) ?: [];
					$itemToRemove = $company_id;
					$updatedArr = removeItemFromArray( $companiesIdsArray, $itemToRemove );
					update_post_meta( $owner_id, 'owner-company', $updatedArr );
				}
				// in brands update companies array
				$brandsIdsArray =  get_post_meta( $company_id, 'company-brand', true) ?: [];
				foreach ( $brandsIdsArray as $brand_id ) {
					$companiesIdsArray = get_post_meta( $brand_id, 'company-brand', true ) ?: [];
					$itemToRemove = $company_id;
					$updatedArr = removeItemFromArray( $companiesIdsArray, $itemToRemove );
					update_post_meta( $brand_id, 'company-brand', $updatedArr );
				}
    	}

			if ( $post->post_type === 'brands' ) {
				$brand_id = intval( $post_id );
				// in companies update brands array
				$companiesIdsArray =  get_post_meta( $brand_id, 'company-brand', true) ?: [];
				foreach ( $companiesIdsArray as $company_id ) {
					$brandsIdsArray = get_post_meta( $company_id, 'company-brand', true ) ?: [];
					$itemToRemove = $brand_id;
					$updatedArr = removeItemFromArray( $brandsIdsArray, $itemToRemove );
					update_post_meta( $company_id, 'company-brand', $updatedArr );
				}
				// delete media
				$image_to_remove = get_post_meta( $brand_id, 'image', true );
				wp_delete_attachment( $image_to_remove, true );
    	}

			if ( $post->post_type === 'sub-brands' ) {
				$sub_brand_id = intval( $post_id );

				// in products update sub-brands array
				$productsIdsArray =  get_post_meta( $sub_brand_id, 'sub-brand-product', true) ?: [];
				foreach ( $productsIdsArray as $product_id ) {
					$subBrandsIdsArray = get_post_meta( $product_id, 'sub-brand-product', true ) ?: [];
					$itemToRemove = $sub_brand_id;
					$updatedArr = removeItemFromArray( $subBrandsIdsArray, $itemToRemove );
					update_post_meta( $product_id, 'sub-brand-product', $updatedArr );
				}

				// in brands update sub-brands array
				$brandsIdsArray = get_post_meta( $sub_brand_id, 'brand-sub-brand', true ) ?: [];
				foreach ( $brandsIdsArray as $brand_id ) {
					$subBrandsIdsArray = get_post_meta( $brand_id, 'brand-sub-brand', true ) ?: [];
					$itemToRemove = $sub_brand_id;
					$updatedArr = removeItemFromArray( $subBrandsIdsArray, $itemToRemove );
					update_post_meta( $brand_id, 'brand-sub-brand', $updatedArr );
				}

				// delete media
				$image_to_remove = get_post_meta( $sub_brand_id, 'image', true );
				wp_delete_attachment( $image_to_remove, true );
    	}

    	if ( $post->post_type === 'products' ) {
				$product_id = intval( $post_id );

				// in brands update products array
				$brandsIdsArray = get_post_meta( $product_id, 'brand-product', true) ?: [];
				foreach ( $brandsIdsArray as $brand_id ) {
					$productsIdsArray = get_post_meta( $brand_id, 'brand-product', true ) ?: [];
					$itemToRemove = $product_id;
					$updatedArr = removeItemFromArray( $productsIdsArray, $itemToRemove );
					update_post_meta( $brand_id, 'brand-product', $updatedArr );
				}

				// in sub-brands update products array
				$subbrandsIdsArray = get_post_meta( $product_id, 'sub-brand-product', true) ?: [];
				foreach ( $subBrandsIdsArray as $sub_brand_id ) {
					$productsIdsArray = get_post_meta( $sub_brand_id, 'sub-brand-product', true ) ?: [];
					$itemToRemove = $product_id;
					$updatedArr = removeItemFromArray( $productsIdsArray, $itemToRemove );
					update_post_meta( $sub_brand_id, 'sub-brand-product', $updatedArr );
				}
    	}

		},
		10, 2
	);

	// ADD CUSTOM GRAPHQL MUTATIONS TO CPTS
	add_action(
		'graphql_post_object_mutation_update_additional_data',
		function ( $post_id, $input, $post_type_object, $mutation_name, $context, $info, $default_post_status, $intended_post_status ) {

			// ON DELETING CPT
			// if( $mutation_name === "deleteBrand" ){
			// 	$brand_id = intval( $post_id );

			// 	update_post_meta( $brand_id, 'company-brand', [] );
			// }

			// ON UPDATING COMPANY
			// if( $mutation_name === "updateCompany" ){ //custom post type name from CPT UI
			// 	$company_id = intval( $post_id );
			// 	if( is_null( $input['companyBrand'] ) ){
			// 		$brandsIdsArray =  get_post_meta( $company_id, 'company-brand', true);
			// 		foreach ( $brandsIdsArray as $brand_id ) {
			// 			$companiesIdsArray = get_post_meta( $brand_id, 'company-brand', true );
			// 			$itemToRemove = $company_id; // Item to remove
			// 			$updatedArr = removeItemFromArray($companiesIdsArray, $itemToRemove);
			// 			update_post_meta( $brand_id, 'company-brand', $updatedArr );
			// 		}
			// 	}
			// }

			if( $mutation_name === "updateBrand" ){ //custom post type name from CPT UI
				$brand_id = intval( $post_id );
				if( $input['companyBrand'] === null ){
					$companiesIdsArray =  get_post_meta( $brand_id, 'company-brand', true ) ?: [];
					foreach ( $companiesIdsArray as $company_id ) {
						$brandsIdsArray = get_post_meta( $company_id, 'company-brand', true ) ?: [];
						$itemToRemove = $brand_id;
						$updatedArr = removeItemFromArray($brandsIdsArray, $itemToRemove);
						update_post_meta( $company_id, 'company-brand', $updatedArr );
					}
				}
			}

			// ON CREATING/UPDATING CPTs
			// -----------------------------------------
			// COMPANIES
			// -----------------------------------------
			if( $post_type_object->name == "companies" ){ //custom post type name from CPT UI
				$company_id = intval( $post_id );

				if( ! empty( $input['ownerCompany'] ) ){
					$owner_id = intval( $input['ownerCompany'] );

					// in owners update companies array
					$tempArrOwners = get_post_meta( $owner_id, 'owner-company', true) ?: [];
					array_push( $tempArrOwners, strval( $company_id ) );
					$uniqueArrayOwners = array_unique($tempArrOwners);
					update_post_meta( $owner_id, 'owner-company', $uniqueArrayOwners );

					// in company update owners array
					// $tempArrOwners = get_post_meta( $company_id, 'owner-company', true) ?: [];
					$tempArrOwners = []; // should be only one item in array
					array_push( $tempArrOwners, strval( $owner_id ) );
					update_post_meta( $company_id, 'owner-company', $tempArrOwners );
				}
				if( $input['ownerCompany'] === null ){
					$ownersIdsArray = get_post_meta( $company_id, 'owner-company', true) ?: [];
					// in owners update companies array
					foreach ( $ownersIdsArray as $owner_id ) {
						$companiesIdsArray = get_post_meta( $owner_id, 'owner-company', true ) ?: [];
						$itemToRemove = $company_id;
						$updatedArr = removeItemFromArray($companiesIdsArray, $itemToRemove);
						update_post_meta( $owner_id, 'owner-company', $updatedArr );
					}

					// in company update owners array
					update_post_meta( $company_id, 'owner-company', [] );
				}

				if( ( $input['companyBrand'] ) === "" ){ // clear all company-brands relations
					$brandsIdsArray =  get_post_meta( $company_id, 'company-brand', true) ?: [];

					// in company update brands array
					update_post_meta( $company_id, 'company-brand', [] );

					// in brands update companies array
					foreach ( $brandsIdsArray as $brand_id ) {
						$companiesIdsArray = get_post_meta( $brand_id, 'company-brand', true ) ?: [];
						$itemToRemove = $company_id;
						$updatedArr = removeItemFromArray($companiesIdsArray, $itemToRemove);
						update_post_meta( $brand_id, 'company-brand', $updatedArr );
					}
				}

			}

			// -----------------------------------------
			// BRANDS
			// -----------------------------------------
			if( $post_type_object->name == "brands" ){ //custom post type name from CPT UI
				$brand_id = intval( $post_id );

				if( ! empty( $input['companyBrand'] ) ){
					$company_id = intval( $input['companyBrand'] );

					// in old companies remove brand
					$arrOldCompanies = get_post_meta( $brand_id, 'company-brand', true) ?: [];
					foreach ( $arrOldCompanies as $oldcompany_id ) {
						$brandsIdsArray = get_post_meta( $oldcompany_id, 'company-brand', true ) ?: [];
						$itemToRemove = $brand_id;
						$updatedArr = removeItemFromArray( $brandsIdsArray, $itemToRemove );
						update_post_meta( $oldcompany_id, 'company-brand', $updatedArr );
					}

					// in new company update brands array
					$tempArrBrands = get_post_meta( $company_id, 'company-brand', true) ?: [];
					array_push( $tempArrBrands, strval( $brand_id ) );
					update_post_meta( $company_id, 'company-brand', array_unique( $tempArrBrands ) );

					// in brand update companies array
					// $tempArrCompanies = get_post_meta( $brand_id, 'company-brand', true) ?: [];
					// array_push( $tempArrCompanies, strval( $company_id ) );
					// update_post_meta( $brand_id, 'company-brand', $tempArrCompanies );
					update_post_meta( $brand_id, 'company-brand',[ $company_id ] );
				}

				if( ( $input['companyBrand'] ) === null ){ // clear all company-brands relations
					$companiesIdsArray =  get_post_meta( $brand_id, 'company-brand', true) ?: [];

					// in brand update companies array
					update_post_meta( $brand_id, 'company-brand', [] );

					// in companies update brands array
					foreach ( $companiesIdsArray as $company_id ) {
						$brandsIdsArray = get_post_meta( $company_id, 'company-brand', true ) ?: [];
						$itemToRemove = $brand_id;
						$updatedArr = removeItemFromArray( $brandsIdsArray, $itemToRemove );
						update_post_meta( $company_id, 'company-brand', $updatedArr );
					}
				}

				// add image
				if( ! empty( $input['image'] ) ){
					// delete previous image
					$image_to_remove = get_post_meta( $brand_id, 'image', true );
					if ( ! empty( $image_to_remove ) ){
						wp_delete_attachment( $image_to_remove, true );
					}

					// update with new image
					update_post_meta( $brand_id, 'image', $input['image'] );
				}

				// remove image
				if( ( $input['image'] ) === null ){
					$image_to_remove = get_post_meta( $brand_id, 'image', true );
					wp_delete_attachment( $image_to_remove, true );
					update_post_meta( $brand_id, 'image', null );
				}

			}

			// -----------------------------------------
			// SUB-BRANDS
			// -----------------------------------------
			if( $post_type_object->name == "sub-brands" ){ //custom post type name from CPT UI
				$subbrand_id = intval( $post_id );

				if( ! empty( $input['brandSubbrand'] ) ){
					$brand_id = intval( $input['brandSubbrand'] );

					// in old brands remove sub-brand
					$brandsIdsArray = get_post_meta( $subbrand_id, 'brand-sub-brand', true) ?: [];
					foreach ( $brandsIdsArray as $loop_brand_id ) {
						$subBrandsIdsArray = get_post_meta( $loop_brand_id, 'brand-sub-brand', true ) ?: [];
						$itemToRemove = $subbrand_id;
						$updatedArr = removeItemFromArray( $subBrandsIdsArray, $itemToRemove );
						update_post_meta( $loop_brand_id, 'brand-sub-brand', $updatedArr );
					}

					// in sub-brand update brands array
					$tempArrBrands = get_post_meta( $subbrand_id, 'brand-sub-brand', true) ?: [];
					$tempArrBrands = []; // should be only one item in array
					array_push( $tempArrBrands, strval( $brand_id ) );
					update_post_meta( $subbrand_id, 'brand-sub-brand', $tempArrBrands );

					// in brand update sub-brands array
					$tempArrSubBrands = get_post_meta( $brand_id, 'brand-sub-brand', true) ?: [];
					array_push( $tempArrSubBrands, strval( $subbrand_id ) );
					$uniqueArraySubBrands = array_unique($tempArrSubBrands);
					update_post_meta( $brand_id, 'brand-sub-brand', $uniqueArraySubBrands );
				}

				// add image
				if( ! empty( $input['image'] ) ){
					// delete previous image
					$image_to_remove = get_post_meta( $subbrand_id, 'image', true );
					if ( ! empty( $image_to_remove ) ){
						wp_delete_attachment( $image_to_remove, true );
					}

					// update with new image
					update_post_meta( $subbrand_id, 'image', $input['image'] );
				}
				// remove image
				if( ( $input['image'] ) === null ){
					$image_to_remove = get_post_meta( $subbrand_id, 'image', true );
					wp_delete_attachment( $image_to_remove, true );
					update_post_meta( $subbrand_id, 'image', null );
				}
			}

			// -----------------------------------------
			// PRODUCTS
			// -----------------------------------------
			if( $post_type_object->name == "products" ){ //custom post type name from CPT UI
				$product_id = intval( $post_id );

				if( isset( $input['size'] ) ){
					$size = $input['size'];
					update_post_meta( $product_id, 'size', $size );
				}
				if( isset( $input['ingredientsText'] ) ){
					$ingredientsText = $input['ingredientsText'];
					update_post_meta( $product_id, 'ingredients_text', $ingredientsText );
				}
				if( isset( $input['nutrition'] ) ){
					$nutrition = $input['nutrition'];
					update_post_meta( $product_id, 'nutrition', $nutrition );
				}

				// STEPPER
				if( isset( $input['batchNumber'] ) ){
					$batchNumber = $input['batchNumber'];
					update_post_meta( $product_id, 'batch_number', $batchNumber );
				}
				if( isset( $input['barcode'] ) ){
					$barcode = $input['barcode'];
					update_post_meta( $product_id, 'barcode', $barcode );
				}
				if( isset( $input['useByDate'] ) ){
					$useByDate = $input['useByDate'];
					update_post_meta( $product_id, 'use_by_date', $useByDate );
				}
				if( isset( $input['packagingType'] ) ){
					$packagingType = $input['packagingType'];
					update_post_meta( $product_id, 'packaging_type', $packagingType );
				}
				if( isset( $input['packagingMaterial'] ) ){
					$packagingMaterial = $input['packagingMaterial'];
					update_post_meta( $product_id, 'packaging_material', $packagingMaterial );
				}
				if( isset( $input['recyclable'] ) ){
					$recyclable = $input['recyclable'];
					update_post_meta( $product_id, 'recyclable', $recyclable );
				}
				if( isset( $input['recycleNumber'] ) ){
					$recycleNumber = $input['recycleNumber'];
					update_post_meta( $product_id, 'recycle_number', $recycleNumber );
				}
				if( isset( $input['recycledMaterial'] ) ){
					$recycledMaterial = $input['recycledMaterial'];
					update_post_meta( $product_id, 'recycled_material', $recycledMaterial );
				}
				if( isset( $input['recycledMaterialPercentage'] ) ){
					$recycledMaterialPercentage = $input['recycledMaterialPercentage'];
					update_post_meta( $product_id, 'recycled_material_percentage', $recycledMaterialPercentage );
				}
				if( isset( $input['country'] ) ){
					$country = $input['country'];
					update_post_meta( $product_id, 'country', $country );
				}
				if( isset( $input['collaborationType'] ) ){
					$collaborationType = $input['collaborationType'];
					update_post_meta( $product_id, 'collaboration_type', $collaborationType );
				}
				if( isset( $input['collaborationWith'] ) ){
					$collaborationWith = $input['collaborationWith'];
					update_post_meta( $product_id, 'collaboration_with', $collaborationWith );
				}
				// END STEPPER

				if( isset( $input['photos'] ) ){
					$photos = $input['photos'];
					update_post_meta( $product_id, 'photos', $photos );
				}

				if( ! empty( $input['brandProduct'] )  ){
					$brand_id = intval( $input['brandProduct'] );

					// in old brands remove product
					$oldbrandsIdsArray = get_post_meta( $product_id, 'brand-product', true) ?: [];
					foreach ( $oldbrandsIdsArray as $loop_brand_id ) {
						$productsIdsArray = get_post_meta( $loop_brand_id, 'brand-product', true ) ?: [];
						$itemToRemove = $product_id;
						$updatedArr = removeItemFromArray( $productsIdsArray, $itemToRemove );
						update_post_meta( $loop_brand_id, 'brand-product', $updatedArr );
					}

					// in product update brands array
					// $tempArrBrands = get_post_meta( $product_id, 'brand-product', true) ?: [];
					$tempArrBrands = []; // should be only one item in array
					array_push( $tempArrBrands, strval( $brand_id ) );
					$uniqueArrayBrands = array_unique($tempArrBrands);
					update_post_meta( $product_id, 'brand-product', $uniqueArrayBrands );

					// in brand update products array
					$tempArrProducts = get_post_meta( $brand_id, 'brand-product', true) ?: [];
					array_push( $tempArrProducts, strval( $product_id ) );
					$uniqueArrayProducts = array_unique($tempArrProducts);
					update_post_meta( $brand_id, 'brand-product', $uniqueArrayProducts );
				}

				if( $input['brandProduct'] === null ){
					$brandsIdsArray =  get_post_meta( $product_id, 'brand-product', true ) ?: [];
					foreach ( $brandsIdsArray as $loop_brand_id ) {
						$productsIdsArray = get_post_meta( $brand_id, 'brand-product', true ) ?: [];
						$itemToRemove = $product_id;
						$updatedArr = removeItemFromArray($productsIdsArray, $itemToRemove);
						update_post_meta( $loop_brand_id, 'brand-product', $updatedArr );
					}
					update_post_meta( $product_id, 'brand-product', null );
				}

				if( ! empty( $input['subBrandProduct'] ) ){
					$sub_brand_id = intval( $input['subBrandProduct'] );

					// in product update brands array
					// $tempArrSubBrands = get_post_meta( $product_id, 'sub-brand-product', true) ?: [];
					$tempArrSubBrands = []; // should be only one item in array
					array_push( $tempArrSubBrands, strval( $sub_brand_id ) );
					$uniqueArraySubBrands = array_unique($tempArrSubBrands);
					update_post_meta( $product_id, 'sub-brand-product', $uniqueArraySubBrands );

					// in sub brand update products array
					$tempArrProducts = get_post_meta( $sub_brand_id, 'sub-brand-product', true) ?: [];
					array_push( $tempArrProducts, strval( $product_id ) );
					$uniqueArrayProducts = array_unique($tempArrProducts);
					update_post_meta( $sub_brand_id, 'sub-brand-product', $uniqueArrayProducts );
				}
				if( $input['subBrandProduct'] === null ){
					$subBrandsIdsArray =  get_post_meta( $product_id, 'sub-brand-product', true ) ?: [];
					foreach ( $subBrandsIdsArray as $sub_brand_id ) {
						$productsIdsArray = get_post_meta( $sub_brand_id, 'sub-brand-product', true ) ?: [];
						$itemToRemove = $product_id;
						$updatedArr = removeItemFromArray($productsIdsArray, $itemToRemove);
						update_post_meta( $sub_brand_id, 'sub-brand-product', $updatedArr );
					}
					update_post_meta( $product_id, 'sub-brand-product', null );
				}

				// -----------------------------------------
 				// PRODUCT SIZES
				// -----------------------------------------
				if( isset( $input['productSizes'] ) ){

					$new_product_sizes = $input['productSizes'];

					$new_pack_sizes = [];
					foreach ( $new_product_sizes as $productSize ) {
						if ( isset( $productSize['pack_sizes']) && is_array( $productSize['pack_sizes'] ) ) {
							foreach ( $productSize['pack_sizes'] as $packSize ) {
								if ( isset($packSize['pack_size_value'] ) ) {
									$new_pack_sizes[] = [ 'pack_size_value' => $packSize['pack_size_value'] ];
								}
							}
						}
					}

					// -----------------------------------------
					// delete media items for deleted product sizes
					if( have_rows('product_sizes', $product_id ) ){

						while ( have_rows('product_sizes', $product_id ) ) : the_row();
							$curr_product_size_image = get_sub_field('product_size_image');
							$curr_product_size_value = get_sub_field('product_size_value');

							$product_size_found = false;
							$product_size_is_updated = false;
							foreach ( $new_product_sizes as $new_product_size ) {
								if ( isset($new_product_size['product_size_value']) && $new_product_size['product_size_value'] == $curr_product_size_value ) {
									$product_size_found = true;
									// break;
								}
								if( isset( $new_product_size['prev_product_size_value'] ) && $new_product_size['prev_product_size_value'] === $curr_product_size_value ){
									$product_size_found = true;
									update_media_title( $curr_product_size_image['ID'], 'product-size( id:'. $product_id . ' | size:'. $new_product_size['product_size_value'] . ')' );
								}
							}
							if ( !$product_size_found ) { // delete media for deleted product size
								if( $curr_product_size_image && isset($curr_product_size_image['ID']) ) {
									wp_delete_attachment( $curr_product_size_image['ID'], true );
								}
							}

							// delete media items for deleted pack sizes
							if( have_rows('pack_sizes') ){

								while ( have_rows( 'pack_sizes' ) ) : the_row();
									$pack_size_value = get_sub_field('pack_size_value');
									$pack_size_image = get_sub_field('pack_size_image');

									$pack_size_found = false;
									foreach ( $new_pack_sizes as $item_pack_size ) {
										if ( isset($item_pack_size['pack_size_value']) && $item_pack_size['pack_size_value'] == $pack_size_value ) {
											$pack_size_found = true;
											break;
										}
									}
									if ( !$pack_size_found ) {
										if( $pack_size_image && isset($pack_size_image['ID']) ) {
											wp_delete_attachment( $pack_size_image['ID'], true );
										}
									}
								endwhile;
							}

						endwhile;
					}

					update_field('product_sizes', $input['productSizes'], $product_id );

					// $productSizesTest = [
					// 	[
					// 		'product_size_image'=> "305",
					// 		'product_size_value' => "15",
					// 		'pack_sizes' =>[
					// 			[
					// 				'pack_size_value' => '12'
					// 				'pack_size_image' => '305'
					// 			],
					// 			[
					// 				'pack_size_value' => '24'
					// 				'pack_size_image' => '305'
					// 			],
					// 		]
					// 	],
					// 	[
					// 		'product_size_value' => "20",
					// 		'product_size_image'=> "305",
					// 		'pack_sizes' => []
					// 	]
					// ];

					// -----------------------------------------
					// delete media for old product sizes array
					// if( have_rows('product_sizes', $product_id ) ){
					// 	$product_size_images_to_remove = [];

					// 	while ( have_rows('product_sizes', $product_id ) ) : the_row();
					// 		$product_size_image = get_sub_field('product_size_image');
					// 		if( $product_size_image && isset($product_size_image['ID']) ) {
					// 			$product_size_images_to_remove[] = $product_size_image['ID'];
					// 		}
					// 	endwhile;

					// 	foreach ( $product_size_images_to_remove as $product_size_image_id ) {
					// 		wp_delete_attachment( $product_size_image_id, true );
					// 	}
					// }
				}

				if( $input['productSizes'] === [] ){
					update_post_meta( $product_id, 'product_sizes', null );
				}

				// update search tags
				$searchTags = [];
				$productBrandsIdsArray = get_post_meta( $product_id, 'brand-product', true );
				foreach ( $productBrandsIdsArray as $productBrandsId ) {
					$brand_title = get_the_title($productBrandsId);
					array_push( $searchTags, strval( $brand_title ) );
				}
				$productSubBrandsIdsArray = get_post_meta( $product_id, 'sub-brand-product', true );
				foreach ( $productSubBrandsIdsArray as $productSubBrandsId ) {
					$subbrand_title = get_the_title($productSubBrandsId);
					array_push( $searchTags, strval( $subbrand_title ) );
				}

				$string = implode(" ", $searchTags);
				update_post_meta( $product_id, 'search_tags', $string );

			}

			// AFFILIATES
			if( $post_type_object->name == "affiliates" ){ //custom post type name from CPT UI
				$affiliate_id = intval( $post_id );

				if( isset( $input['type'] ) ){
					$type = $input['type'];
					update_post_meta( $affiliate_id, 'type', $type );
				}

				if( isset( $input['program'] ) ){
					$program = $input['program'];
					update_post_meta( $affiliate_id, 'program', $program );
				}

				if( isset( $input['linkCode'] ) ){
					$linkCode = $input['linkCode'];
					update_post_meta( $affiliate_id, 'link_code', $linkCode );
				}

				if( isset( $input['part1Type'] ) ){
					$part1Type = $input['part1Type'];
					update_post_meta( $affiliate_id, 'part1_type', $part1Type );
				}

				if( isset( $input['part2Type'] ) ){
					$part2Type = $input['part2Type'];
					update_post_meta( $affiliate_id, 'part2_type', $part2Type );
				}

				if( isset( $input['part3Type'] ) ){
					$part3Type = $input['part3Type'];
					update_post_meta( $affiliate_id, 'part3_type', $part3Type );
				}

				if( isset( $input['part4Type'] ) ){
					$part4Type = $input['part4Type'];
					update_post_meta( $affiliate_id, 'part4_type', $part4Type );
				}

				if( isset( $input['part1Value'] ) ){
					$part1Value = $input['part1Value'];
					update_post_meta( $affiliate_id, 'part1_value', $part1Value );
				}
				if( isset( $input['part2Value'] ) ){
					$part2Value = $input['part2Value'];
					update_post_meta( $affiliate_id, 'part2_value', $part2Value );
				}
				if( isset( $input['part3Value'] ) ){
					$part3Value = $input['part3Value'];
					update_post_meta( $affiliate_id, 'part3_value', $part3Value );
				}
				if( isset( $input['part4Value'] ) ){
					$part4Value = $input['part4Value'];
					update_post_meta( $affiliate_id, 'part4_value', $part4Value );
				}
				if( isset( $input['linkFormat'] ) ){
					$linkFormat = $input['linkFormat'];
					update_post_meta( $affiliate_id, 'link_format', $linkFormat );
				}

			}

		},
		10,8
	);

	// ADD CUSTOM GRAPHQL MUTATIONS TO USERS
	add_action(
		'graphql_user_object_mutation_update_additional_data',
		function ( $user_id, $input, $mutation_name, $context, $info ){
			if( $mutation_name == "createUser" || $mutation_name == "updateUser" ){
				if( isset( $input['permissionsCompany'] ) ){
					update_user_meta( $user_id, 'company', $input['permissionsCompany'] );
				}
				if( isset( $input['permissionsProduct'] ) ){
					update_user_meta( $user_id, 'product', $input['permissionsProduct'] );
				}
				if( isset( $input['permissionsBrand'] ) ){
					update_user_meta( $user_id, 'brand', $input['permissionsBrand'] );
				}
				if( isset( $input['permissionsRetailer'] ) ){
					update_user_meta( $user_id, 'retailer', $input['permissionsRetailer'] );
				}
				if( isset( $input['permissionsSubBrand'] ) ){
					update_user_meta( $user_id, 'subBrand', $input['permissionsSubBrand'] );
				}
				if( isset( $input['permissionsBuyLinks'] ) ){
					update_user_meta( $user_id, 'buyLinks', $input['permissionsBuyLinks'] );
				}
				if( isset( $input['permissionsAffiliates'] ) ){
					update_user_meta( $user_id, 'affiliates', $input['permissionsAffiliates'] );
				}
				if( isset( $input['permissionsIngredients'] ) ){
					update_user_meta( $user_id, 'ingredients', $input['permissionsIngredients'] );
				}
			}
		},
		10,8
	);

	// ADD NEW INPUTS TO MUTATION
	add_filter(
		'graphql_input_fields',
		function ( $fields, $type_name, $config ) {
			// company
			if ( $type_name == "CreateCompanyInput" ) {
				$fields = array_merge( $fields, [
					'ownerCompany' => [
						'type' => 'ID',
						'description' => 'owner ID',
					],
				]);
			}
			if ( $type_name == "UpdateCompanyInput" ) {
				$fields = array_merge( $fields, [
					'companyBrand' => [
						'type' => 'ID',
						'description' => 'brand ID',
					],
					'ownerCompany' => [
						'type' => 'ID',
						'description' => 'owner ID',
					],
				]);
			}

			// brand
			if ( $type_name == "CreateBrandInput" ) {
				$fields = array_merge( $fields, [
					'companyBrand' => [
						'type' => 'ID',
						'description' => 'company ID',
					],
					'image' =>[
						'type' => 'ID',
						'description' => 'image ID',
					]
				]);
			}
			if ( $type_name == "UpdateBrandInput" ) {
				$fields = array_merge( $fields, [
					'companyBrand' => [
						'type' => 'ID',
						'description' => 'company ID',
					],
					'image' =>[
						'type' => 'ID',
						'description' => 'image ID',
					]
				]);
			}

			// subbrand
			if ( $type_name == "CreateSubBrandInput" ) {
				$fields = array_merge( $fields, [
					'brandSubbrand' => [
						'type' => 'ID',
						'description' => 'subbrand ID',
					],
					'image' =>[
						'type' => 'ID',
						'description' => 'image ID',
					]
				]);
			}
			if ( $type_name == "UpdateSubBrandInput" ) {
				$fields = array_merge( $fields, [
					'brandSubbrand' => [
						'type' => 'ID',
						'description' => 'subbrand ID',
					],
					'image' =>[
						'type' => 'ID',
						'description' => 'image ID',
					]
				]);
			}

			// product
			if ( $type_name == "CreateProductInput" ) {
				$fields = array_merge( $fields, [
					'productSizes' => [
            'type' => ['list_of' => 'ProductSizeInput'],
            'description' => 'Array of Product Sizes',
        	],
				]);
				$fields = array_merge( $fields, [
					'brandProduct' => [
						'type' => 'ID',
						'description' => 'product ID',
					],
				]);
			}
			if ( $type_name == "UpdateProductInput" ) {
				$fields = array_merge( $fields, [
					'photos' => [
            'type' => ['list_of' => 'ID'],
            'description' => 'Array of product image ids',
        	],
				]);

				$fields = array_merge( $fields, [
					'size' => [
            'type' => 'String',
            'description' => 'Product size for nutrition',
        	],
				]);
				$fields = array_merge( $fields, [
					'ingredientsText' => [
            'type' => 'String',
            'description' => 'Product ingredients',
        	],
				]);
				$fields = array_merge( $fields, [
					'nutrition' => [
            'type' => 'String',
            'description' => 'Product nutrition',
        	],
				]);

				// STEPPER
				$fields = array_merge( $fields, [
					'batchNumber' => [
            'type' => 'String',
            'description' => 'Product batch number',
        	],
				]);
				$fields = array_merge( $fields, [
					'barcode' => [
            'type' => 'String',
            'description' => 'Product barcode',
        	],
				]);
				$fields = array_merge( $fields, [
					'useByDate' => [
            'type' => 'String',
            'description' => 'Product use by date',
        	],
				]);
				$fields = array_merge( $fields, [
					'packagingType' => [
            'type' => 'String',
            'description' => 'Product packaging type',
        	],
				]);
				$fields = array_merge( $fields, [
					'packagingMaterial' => [
            'type' => 'String',
            'description' => 'Product packaging material',
        	],
				]);
				$fields = array_merge( $fields, [
					'recyclable' => [
            'type' => 'String',
            'description' => 'Product recyclable',
        	],
				]);
				$fields = array_merge( $fields, [
					'recycleNumber' => [
            'type' => 'String',
            'description' => 'Product recycle number',
        	],
				]);
				$fields = array_merge( $fields, [
					'recycledMaterial' => [
            'type' => 'String',
            'description' => 'Product recycled material',
        	],
				]);
				$fields = array_merge( $fields, [
					'recycledMaterialPercentage' => [
            'type' => 'String',
            'description' => 'Product recycled material percentage',
        	],
				]);
				$fields = array_merge( $fields, [
					'country' => [
            'type' => 'String',
            'description' => 'Product country',
        	],
				]);
				$fields = array_merge( $fields, [
					'collaborationType' => [
            'type' => 'String',
            'description' => 'Product collaboration type',
        	],
				]);
				$fields = array_merge( $fields, [
					'collaborationWith' => [
            'type' => 'String',
            'description' => 'Product collaboration with',
        	],
				]);
				// END STEPPER

				$fields = array_merge( $fields, [
					'productSizes' => [
            'type' => ['list_of' => 'ProductSizeInput'],
            'description' => 'Array of Product Sizes',
        	],
				]);
				$fields = array_merge( $fields, [
					'brandProduct' => [
						'type' => 'ID',
						'description' => 'brand ID',
					],
				]);
				$fields = array_merge( $fields, [
					'subBrandProduct' => [
						'type' => 'ID',
						'description' => 'subBrand ID',
					],
				]);
			}

			// users
			if ( $type_name == "CreateUserInput" || $type_name == "UpdateUserInput" ) {
				$fields = array_merge( $fields, [
					'permissionsCompany' => [
						'type' => 'Boolean',
						'description' => 'permission to edit companies',
					],
					'permissionsProduct' => [
						'type' => 'Boolean',
						'description' => 'permission to edit products',
					],
					'permissionsBrand' => [
						'type' => 'Boolean',
						'description' => 'permission to edit brands',
					],
					'permissionsRetailer' => [
						'type' => 'Boolean',
						'description' => 'permission to edit retailers',
					],
					'permissionsSubBrand' => [
						'type' => 'Boolean',
						'description' => 'permission to edit sub-brands',
					],
					'permissionsBuyLinks' => [
						'type' => 'Boolean',
						'description' => 'permission to edit buy-links',
					],
					'permissionsAffiliates' => [
						'type' => 'Boolean',
						'description' => 'permission to edit affiliates',
					],
					'permissionsIngredients' => [
						'type' => 'Boolean',
						'description' => 'permission to edit ingredients',
					],
				]);
			}

			// affiliate
			if ( $type_name == "CreateAffiliateInput" ) {
				$fields = array_merge( $fields, [
					'type' => [
						'type' => 'String',
						'description' => 'Affiliate type',
					],
					'program' => [
						'type' => 'String',
						'description' => 'Affiliate type',
					],
					'linkCode' => [
						'type' => 'String',
						'description' => 'Affiliate link code',
					],
					'linkFormat' => [
						'type' => 'String',
						'description' => 'Affiliate link format',
					],
				]);
			}
			if ( $type_name == "UpdateAffiliateInput" ) {
				$fields = array_merge( $fields, [
					'type' => [
						'type' => 'String',
						'description' => 'Affiliate type',
					],
					'program' => [
						'type' => 'String',
						'description' => 'Affiliate program',
					],
					'linkCode' => [
						'type' => 'String',
						'description' => 'Affiliate link code',
					],

					'part1Type' => [
						'type' => 'String',
						'description' => 'Affiliate link Part 1 type',
					],
					'part2Type' => [
						'type' => 'String',
						'description' => 'Affiliate link Part 2 type',
					],
					'part3Type' => [
						'type' => 'String',
						'description' => 'Affiliate link Part 3 type',
					],
					'part4Type' => [
						'type' => 'String',
						'description' => 'Affiliate link Part 4 type',
					],
					'part1Value' => [
						'type' => 'String',
						'description' => 'Affiliate link Part 1',
					],
					'part2Value' => [
						'type' => 'String',
						'description' => 'Affiliate link Part 2',
					],
					'part3Value' => [
						'type' => 'String',
						'description' => 'Affiliate link Part 3',
					],
					'part4Value' => [
						'type' => 'String',
						'description' => 'Affiliate link Part 4',
					],

					'linkFormat' => [
						'type' => 'String',
						'description' => 'Affiliate link format',
					],

				]);
			}

			return $fields;
		},
		10, 3
	);


}
add_action( 'init', 'vs_extend_graphql_mutations_init' );
