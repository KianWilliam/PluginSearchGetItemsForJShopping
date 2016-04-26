<?php 

/**
 * @package Plugin plg_search_searchshopforjoomshopping for Joomla! 3.x
 * @version $Id: plg_search_searchshopforjoomshopping 1.0.0 2016-04-17 23:26:33Z $
 * @author Kian William Nowrouzian
 * @copyright (C) 2015- Kian William Nowrouzian
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 
 This file is part of plg_search_searchshopforjoomshopping.
    searchshopforjoomshopping is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    plugin searchshopforjoomshopping is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with searchshopforjoomshopping.  If not, see <http://www.gnu.org/licenses/>.
 
**/


?>
<?php
defined('_JEXEC') or die;
class PlgSearchshopforjoomshopping extends JPlugin
{
	

	
	
	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
				
		$db = JFactory::getDbo();
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		
		$tag = JFactory::getLanguage()->getTag();
		
		$limit = $this->params->def('search_limit', 50);
		$text = trim($text);

		if ($text == '')
		{
			return array();
		}
				

		switch ($phrase)
		{
			case 'exact':
				$text = $db->quote('%' . $db->escape($text, true) . '%', false);
				$wheres2 = array();
				$wheres2[] = $db->quoteName('a.product_price').' LIKE ' . $text;
				$wheres2[] = $db->quoteName('a.name_en-GB').' LIKE ' . $text;
				$wheres2[] = $db->quoteName('a.description_en-GB').' LIKE ' . $text;				
				$where = '(' . implode(') OR (', $wheres2) . ')';
				break;
			case 'any':
			case 'all':
			default:
				$words = explode(' ', $text);
				$wheres = array();

				foreach ($words as $word)
				{
					$word = $db->quote('%' . $db->escape($word, true) . '%', false);
					$wheres2 = array();
					$wheres2[] =$db->quoteName('a.product_price').' LIKE ' . $word;
					$wheres2[] = $db->quoteName('a.name_en-GB').' LIKE ' . $word;
					$wheres2[] = $db->quoteName('a.description_en-GB').' LIKE ' . $word;					
					$wheres[] = implode(' OR ', $wheres2);
				}

				$where = '(' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $wheres) . ')';
				break;
				
		}
		
		$cols = $db->getTableColumns('#__jshopping_products');
		
		if(!isset($cols['name_en']))
		{
			$db->getQuery(true);
			$db->setQuery('ALTER #__jshopping_products ADD name_en TEXT NULL');
			$db->query();
		}
		if(!isset($cols['description_en']))
		{
			$db->getQuery(true);
			$db->setQuery('ALTER #__jshopping_products ADD description_en TEXT NULL');
			$db->query();
		}
		if(!isset($cols['GB']))
		{
			$db->getQuery(true);
			$db->setQuery('ALTER TABLE #__jshopping_products ADD GB INT DEFAULT 1');
			$db->query();
		}
		
		
        $cols = $db->getTableColumns('#__jshopping_categories');		
		if(!isset($cols['name_en']))
		{
			$db->setQuery('ALTER #__jshopping_categories ADD name_en TEXT NULL');
			$db->query();
		}

		$order = 'a.name_en-GB, b.name_en-GB';
		$query = $db->getQuery(true);
	    $query->select('m.product_id , m.category_id , a.product_id as pidb ,'.$query->concatenate(array("a.product_id", "m.category_id"), "(Searched Item)").' as title ,\'\' as created, ' . $query->concatenate(array("a.product_id", "m.category_id"), ",") . ' as text, '.$query->concatenate(array("a.product_id", "m.category_id"), "/").' as section, ' .$query->concatenate(array("a.product_id", "m.category_id"), "<--Click-->"). ' as browsernav, a.product_price AS pp,'.$db->quoteName('a.name_en-GB').' AS pn,'.$db->quoteName('a.description_en-GB').' , b.category_id As cid, '.$db->quoteName('b.name_en-GB').'  AS catname')
	    ->from('#__jshopping_products_to_categories AS m')
        ->join('INNER', '#__jshopping_products AS a ON (a.product_id = m.product_id)')
		->join('INNER', '#__jshopping_categories AS b ON (b.category_id =  m.category_id)')
		->where('(' . $where . ')')
		->order($order);
		
		$db->setQuery($query, 0, $limit);
		
		
		$rows = $db->loadObjectList();
		
		if ($rows)
		{
			
			foreach ($rows as $key => $row)
			{
		
				$rows[$key]->href = 'index.php?option=com_jshopping&view=product&task=view&category_id=' . $row->cid . '&product_id=' . $row->pidb;
				$rows[$key]->text = "Category :".$row->catname . ", Product Name :". $row->pn .", Price:".floor($row->pp)."€";
			}
		}

		return $rows;	


	}	
	


}
