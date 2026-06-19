<?php
/**
 * Address Book model — CRUD for saved Turkish addresses (free tier: cap = 2).
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Address_Book_Model
 *
 * Manages saved Turkish addresses per user. Free tier enforces a cap of 2.
 * The premium plugin ships its own version of this class that overrides
 * can_save_more() to allow unlimited addresses when a valid license is active.
 */
class Cecomsmarad_Address_Book_Model {

	/** Maximum saved addresses for the free tier. */
	protected const FREE_CAP = 2;

	/**
	 * Get all saved addresses for a user, ordered by creation date ascending.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return object[] Array of row objects.
	 */
	public function get_addresses( int $user_id ): array {
		global $wpdb;

		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- User-specific query; caching would require per-user cache invalidation on every write.
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}cecomsmarad_address_book` WHERE user_id = %d ORDER BY created_at ASC",
				$user_id
			)
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get a single saved address by ID, verified to belong to the given user.
	 *
	 * @param int $id      Address row ID.
	 * @param int $user_id WordPress user ID.
	 * @return object|null Row object, or null if not found or ownership mismatch.
	 */
	public function get_address( int $id, int $user_id ): ?object {
		global $wpdb;

		$result = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}cecomsmarad_address_book` WHERE id = %d AND user_id = %d LIMIT 1",
				$id,
				$user_id
			)
		);

		return $result ?: null;
	}

	/**
	 * Count saved addresses for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int Number of saved addresses.
	 */
	public function get_count( int $user_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$wpdb->prefix}cecomsmarad_address_book` WHERE user_id = %d",
				$user_id
			)
		);
	}

	/**
	 * Whether the user can save another address.
	 *
	 * Free tier: returns true when the user has fewer than FREE_CAP addresses.
	 * Premium plugin overrides this to check the license.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool True if the user may save another address.
	 */
	public function can_save_more( int $user_id ): bool {
		return $this->get_count( $user_id ) < static::FREE_CAP;
	}

	/**
	 * Save a new address for the user.
	 *
	 * Returns false if the user is at the cap. Otherwise inserts and returns the new row ID.
	 *
	 * @param int    $user_id      WordPress user ID.
	 * @param string $nickname     User-chosen label (max 50 chars, already sanitized).
	 * @param string $address_type 'billing' or 'shipping'.
	 * @param array  $data         Keys: province_code, province_name, district_name, neighborhood, postcode, address_1.
	 * @return int|false New row ID on success, false if at cap or insert failed.
	 */
	public function save_address( int $user_id, string $nickname, string $address_type, array $data ): int|false {
		if ( ! $this->can_save_more( $user_id ) ) {
			return false;
		}

		global $wpdb;

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomsmarad_address_book',
			array(
				'user_id'       => $user_id,
				'nickname'      => $nickname,
				'address_type'  => in_array( $address_type, array( 'billing', 'shipping' ), true ) ? $address_type : 'billing',
				'province_code' => $data['province_code'] ?? '',
				'province_name' => $data['province_name'] ?? '',
				'district_name' => $data['district_name'] ?? '',
				'neighborhood'  => $data['neighborhood'] ?? '',
				'postcode'      => $data['postcode'] ?? '',
				'address_1'     => $data['address_1']  ?? '',
				'first_name'    => $data['first_name'] ?? '',
				'last_name'     => $data['last_name']  ?? '',
				'company'       => $data['company']    ?? '',
				'phone'         => $data['phone']      ?? '',
				'email'         => $data['email']      ?? '',
				'country'       => $data['country']    ?? '',
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Delete a saved address. Ownership is enforced via the WHERE clause.
	 *
	 * @param int $id      Address row ID.
	 * @param int $user_id WordPress user ID.
	 * @return bool True if a row was deleted.
	 */
	public function delete_address( int $id, int $user_id ): bool {
		global $wpdb;

		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomsmarad_address_book',
			array( 'id' => $id, 'user_id' => $user_id ),
			array( '%d', '%d' )
		);

		return false !== $result && $result > 0;
	}

	/**
	 * Rename a saved address. Ownership is enforced via the WHERE clause.
	 *
	 * @param int    $id       Address row ID.
	 * @param int    $user_id  WordPress user ID.
	 * @param string $nickname New nickname (max 50 chars, already sanitized).
	 * @return bool True if the row was updated.
	 */
	public function rename_address( int $id, int $user_id, string $nickname ): bool {
		global $wpdb;

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomsmarad_address_book',
			array( 'nickname' => $nickname ),
			array( 'id' => $id, 'user_id' => $user_id ),
			array( '%s' ),
			array( '%d', '%d' )
		);

		return false !== $result;
	}

	/**
	 * Update all fields of a saved address. Ownership is enforced via the WHERE clause.
	 *
	 * @param int    $id       Address row ID.
	 * @param int    $user_id  WordPress user ID.
	 * @param string $nickname New nickname (max 50 chars, already sanitized).
	 * @param array  $data     Keys: province_code, province_name, district_name, neighborhood, postcode, address_1, first_name, last_name, company, phone, email.
	 * @return bool True if the row was updated.
	 */
	public function update_address( int $id, int $user_id, string $nickname, array $data ): bool {
		global $wpdb;

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomsmarad_address_book',
			array(
				'nickname'      => $nickname,
				'province_code' => $data['province_code'] ?? '',
				'province_name' => $data['province_name'] ?? '',
				'district_name' => $data['district_name'] ?? '',
				'neighborhood'  => $data['neighborhood']  ?? '',
				'postcode'      => $data['postcode']       ?? '',
				'address_1'     => $data['address_1']      ?? '',
				'first_name'    => $data['first_name']     ?? '',
				'last_name'     => $data['last_name']      ?? '',
				'company'       => $data['company']        ?? '',
				'phone'         => $data['phone']          ?? '',
				'email'         => $data['email']          ?? '',
				'country'       => $data['country']        ?? '',
			),
			array(
				'id'      => $id,
				'user_id' => $user_id,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d', '%d' )
		);

		return false !== $result;
	}
}
