<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Media\Scale;


/**
 * Image rescale job controller.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Rescale product images' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Rescales product images to the new sizes' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->getContext();
		$cntl = \Aimeos\Controller\Common\Media\Factory::create( $context );
		$manager = \Aimeos\MShop::create( $context, 'media' );

		$search = $manager->createSearch();
		$expr = array(
			$search->compare( '==', 'media.domain', 'product' ),
			$search->compare( '=~', 'media.mimetype', 'image/' ),
		);
		$search->setConditions( $search->combine( '&&', $expr ) );
		$search->setSortations( array( $search->sort( '+', 'media.id' ) ) );

		$start = 0;

		do
		{
			$search->setSlice( $start );
			$items = $manager->searchItems( $search );

			foreach( $items as $item )
			{
				try
				{
					$cntl->scale( $item, 'fs-media' );
					$manager->saveItem( $item );
				}
				catch( \Exception $e )
				{
					$msg = sprintf( 'Scaling media item "%1$s" failed: %2$s', $item->getId(), $e->getMessage() );
					$context->getLogger()->log( $msg );
				}
			}

			$count = count( $items );
			$start += $count;
		}
		while( $count === $search->getSliceSize() );
	}
}
