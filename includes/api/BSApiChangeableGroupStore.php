<?php
/**
 * This class serves as a backend for the usermanager group store.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://bluespice.com
 *
 * @author     Markus Glaser <glaser@hallowelt.com>
 * @package    Bluespice_Extensions
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 *
 */
class BSApiChangeableGroupStore extends BSApiGroupStore {
	/**
	 * @param string $sQuery
	 * @return array - List of of group objects with additional data
	 */
	protected function makeData( $sQuery = '' ) {
		$aData = parent::makeData( $sQuery );
		$aChangeableData = array();
		$aChangeableGroups = $this->getUser()->changeableGroups();
		$aChangeableGroupsMerged = array_unique( array_merge(
			$aChangeableGroups['add'],
			$aChangeableGroups['add-self'],
			$aChangeableGroups['remove'],
			$aChangeableGroups['remove-self']
		));

		foreach ( $aData as $aGroupDef ) {
			if( !in_array( $aGroupDef->group_name, $aChangeableGroupsMerged ) ) {
				continue;
			}
			$aChangeableData[] = $aGroupDef;
		}

		return $aChangeableData;
	}
}
