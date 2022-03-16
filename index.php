<?php

require_once 'vendor/autoload.php';

use IntervalTree\NumericRangeExclusive;
use IntervalTree\IntervalTree;

$block_interval = 30; // 30 minutes.

$blocks = array(
	1647424800, // 10:00
	1647426600, // 10:30
	1647428400, // 11:00
	1647430200, // 11:30
	1647432000, // 12:00
	1647433800, // 12:30
	1647435600, // 13:00
);

/**
 * Existing bookings
 * 
 * Booked slots:
 *   10:30 - 11:00
 *   11:30 - 12:00
 */
$existing_bookings = array(
	1647426600, // 10:30
	1647430200, // 11:30
);

/**
 * Availablitiy rules.
 * Not available from 12:00 - 12:30
 */
$rule_1 = new \stdClass();
$rule_1->from = 1647432000;
$rule_1->to = strtotime( "+{$block_interval} minutes", 1647432000 );
$rule_1->bookable = false;

$availability_rules = array( $rule_1 );

/**
 * Create Interval tree from $blocks.
 */
class BookingBlock extends NumericRangeExclusive {
	public $bookable = true;

	public function __construct( $start_time, $end_time ) {
		parent::__construct( $start_time, $end_time );
	}

	public function setBookable( $bookable ) {
		$this->bookable = $bookable;
	}
}

$block_range = array();

foreach ( $blocks as $block ) {
	$start_epoch = $block;
	$end_epoch   = strtotime( "+{$block_interval} minutes", $start_epoch );

	$block_range[] = new BookingBlock( $start_epoch, $end_epoch );
}

/**
 * This is the tree of all the blocks that are
 * possible.s
 */
$tree = new IntervalTree( $block_range );

/**
 * Detect which blocks are available
 * based on availability rules.
 */
foreach ( $availability_rules as $rule ) {
	$results = $tree->search( new NumericRangeExclusive( $rule->from, $rule->to ) );

	foreach ( $results as $result ) {
		$result->setBookable( $rule->bookable );
	}
}

/**
 * Detect which blocks are available
 * based on existing bookings.
 */
foreach ( $existing_bookings as $existing_booking ) {
	$results = $tree->search( new NumericRangeExclusive( $existing_booking, strtotime( "+{$block_interval} minutes", $existing_booking ) ) );

	foreach ( $results as $result ) {
		$result->setBookable( false );
	}
}

$final_blocks = $tree->search( new NumericRangeExclusive( 1647424800, 1647435600 ) );
?>

<table>
	<thead>
		<tr>
			<th>Block</th>
			<th>Availability</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $final_blocks as $block ) : ?>
			<tr>
				<td><?php echo date( 'H:i', $block->getStart() ); ?></td>
				<td><?php echo $block->bookable ? 'Bookable' : 'Not bookable'; ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<style>
	table, th, td {
		border: 1px solid black;
		border-collapse: collapse;
	}

	th, td {
		padding: 15 8px;
	}
</style>