<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		$entity = [
			'id' => $this->id,
		];
		
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column};
		}
		
		$entity['reference'] = $this->reference ?? null;
		$entity['slug'] = $this->slug ?? null;
		$entity['url'] = $this->url ?? null;
		$entity['phone_intl'] = $this->phone_intl ?? null;
		$entity['created_at_formatted'] = $this->created_at_formatted ?? null;
		$entity['user_photo_url'] = $this->user_photo_url ?? null;
		$entity['country_flag_url'] = $this->country_flag_url ?? null;
		$entity['price_label'] = $this->price_label ?? t('price');
		$entity['price_formatted'] = $this->price_formatted ?? null;
		$entity['visits_formatted'] = $this->visits_formatted ?? null;
		$entity['distance_info'] = $this->distance_info ?? null;
		$entity['count_pictures'] = $this->count_pictures ?? 0;
		
		$defaultPicture = config('larapen.core.picture.default');
		$defaultPictureUrl = imgUrl($defaultPicture);
		$entity['picture'] = [
			'filename' => $this->picture ?? $defaultPicture,
			'url'      => [
				'full'   => $this->picture_url ?? $defaultPictureUrl,
				'small'  => $this->picture_url_small ?? $defaultPictureUrl,
				'medium' => $this->picture_url_medium ?? $defaultPictureUrl,
				'big'    => $this->picture_url_big ?? $defaultPictureUrl,
			],
		];
		
		if (isset($this->distance)) {
			$entity['distance'] = $this->distance;
		}
		
		$embed = request()->filled('embed') ? explode(',', request()->query('embed')) : [];
		
		if (in_array('country', $embed)) {
			$entity['country'] = new CountryResource($this->whenLoaded('country'));
		}
		if (in_array('user', $embed)) {
			$entity['user'] = new UserResource($this->whenLoaded('user'));
		}
		if (in_array('category', $embed)) {
			$entity['category'] = new CategoryResource($this->whenLoaded('category'));
		}
		if (in_array('postType', $embed)) {
			$entity['postType'] = new PostTypeResource($this->whenLoaded('postType'));
		}
		if (in_array('city', $embed)) {
			$entity['city'] = new CityResource($this->whenLoaded('city'));
		}
		if (in_array('currency', $embed)) {
			$entity['currency'] = new CurrencyResource($this->whenLoaded('currency'));
		}
		if (in_array('payment', $embed)) {
			$entity['payment'] = new PaymentResource($this->whenLoaded('payment'));
		}
		if (in_array('possiblePayment', $embed)) {
			$entity['possiblePayment'] = new PaymentResource($this->whenLoaded('possiblePayment'));
		}
		if (in_array('savedByLoggedUser', $embed)) {
			$entity['savedByLoggedUser'] = UserResource::collection($this->whenLoaded('savedByLoggedUser'));
		}
		if (in_array('pictures', $embed)) {
			$entity['pictures'] = PictureResource::collection($this->whenLoaded('pictures'));
		}
		
		// Reviews Plugin
		if (config('plugins.reviews.installed')) {
			$entity['rating_cache'] = $this->rating_cache ?? 0;
			$entity['rating_count'] = $this->rating_count ?? 0;
			// Warning: To prevent SQL queries in loop,
			// Never embed 'userRating' and 'countUserRatings' when collection will be returned
			if (in_array('userRating', $embed)) {
				$entity['p_user_rating'] = $this->userRating();
			}
			if (in_array('countUserRatings', $embed)) {
				$entity['p_count_user_ratings'] = $this->countUserRatings();
			}
		}
		
		return $entity;
	}
}
