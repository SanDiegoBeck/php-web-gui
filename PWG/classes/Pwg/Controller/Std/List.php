<?php

class Pwg_Controller_Std_List extends Pwg_Controller_MDI_Window implements Pwg_I_RecordList {
	
	protected $finderClass = false;
	
	protected $mapperClass = false;
	
	protected $anySortCriterionName = 'anySort';
	
	protected $anySubstringCriterionName = 'anySubstring';
	
	protected $defaultOrderingColName = true;
	
	protected $defaultOrderingIsAsc = true;
	
	protected $colToPropMap = array();
    
    /**
     * @var Pwg_Data_Source
     */
    protected $dsData = false;
    
    /**
     * @var Pwg_Yui_Paginator
     */
    public $paginator = false;
    
    /**
     * @var Pwg_Data_Filter
     */
    protected $fltFilter = false;
    
    /**
     * @var Pwg_Table
     */
    protected $tblList = false;
    
    protected function doGetColumnPrototypes() {
        return array(
        );
    }
    
    function getMapperClass() {
    	return $this->mapperClass;
    }

    protected function doGetFinderPrototype() {
        return array();
    }   
    
    protected function doGetFinderPrototype() {
        return array();
    }
    
    protected function doOnGetControlPrototypes(array & $prototypes) {
    	
    	if ($this->finderClass !== false) {
    		$finder = Pwg_Autoparams::factory($this->doGetFinderPrototype(), $this->finderClass);
    		if ($this->mapperClass === false) $this->mapperClass = $finder->getMapperClassForCollection();
    		$alias = $finder->getPrimaryAlias();
    	}
    	
    	$columnPrototypes = $this->doGetColumnPrototypes();
    	if (strlen($this->anySortCriterionName)) {
    		foreach ($columnPrototypes as $k => $col) {
    			if (!isset($columnPrototypes[$k]['sortable'])) $columnPrototypes[$k]['sortable'] = true; 
    		}
    	}
    	
    	Ac_Util::ms($prototypes, array(
        
            'pnlLayout' => array(
                
                'template' => '
                    <table cols="3">
                        <tr>
                            <td style="padding: 0.5em">
                            	{btnCreate}
                            	{btnOpenDetails}
                            </td>
                        	<td style="padding: 0.5em">
                            	{pnlFilters}  
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 0.5em" colspan="2">
                                {paginator}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="padding: 0.5em">
                                {tblList}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 0.5em" colspan="3">
                                {dnNavigator}
                            </td>
                        </tr>
                    </table>
                ',
            ),
            
            'pnlFilters' => array(
            	'displayParentPath' => '../pnlLayout',
            	'template' => '{lng:search}: {txtFilter} {lstSort}',
            ),
            
            'txtFilter' => array(
            	'displayParentPath' => '../pnlFilters',
            	'containerIsBlock' => false,
            	'size' => 15,
            ),
            
            'lstSort' => array(
            	'displayParentPath' => '../pnlFilters',
            	'containerIsBlock' => false,
            	'options' => array(
            		array('label' => '(unsorted)', 'value' => null),
            	),
            ),
        
            'btnCreate' => array(
            	'label' => new Pwg_Lang_String('create'),
            	'containerIsBlock' => false,
            	'displayParentPath' => '../pnlLayout',
            ),
            
            'btnOpenDetails' => array(
            	'label' => new Pwg_Lang_String('open_details'),
            	'containerIsBlock' => false,
            	'displayParentPath' => '../pnlLayout',
            ),
            
            'dsData' => array(
                'mapperClass' => $this->mapperClass,
            	'debug' => true,
            ),
            
            'paginator' => array(
                'displayParentPath' => '../pnlLayout',
                'class' => 'Pwg_Yui_Paginator',
                'rowsPerPage' => 10,
                'dataSourcePath' => '../dsData',
                'containerIsBlock' => false, 
            ),
        
            'bndList' => array(
                'class' => 'Pwg_Data_Binder_Records',
                'dataControlPath' => '../tblList',
                'dataSourcePath' => '../dsData',
            	'paginatorPath' => '../paginator',
            ),
            
            'tblList' => array(
                'class' => 'Pwg_Table',
                'displayParentPath' => '../pnlLayout',
                'columnPrototypes' => $columnPrototypes,
            	//'scrollable' => true,
                //'height' => '200px',
            ),
            
            'dnNavigator' => array(
                'dataSourcePath' => '../dsData', 
                'displayParentPath' => '../pnlLayout',
            	'hasBtnFirst' => false,
            	'hasBtnNext' => false,
            	'hasBtnPrev' => false,
            	'hasBtnLast' => false,
            	'hasBtnNew' => false,
            	'hasBtnSave' => false,
            	'hasBtnCancel' => false,
            	'hasBtnReload' => true,
            	'deleteConfirmation' => new Pwg_Lang_String('deleteRecordConfirmation'),
            ),
            
        ));
        
        if ($this->finderClass !== false) {
        	Ac_Util::ms($prototypes, array(            
            	'fltFilter' => array(
            		'class' => 'Pwg_Data_Filter',
            		'finder' => $finder,
            		'dataSourcePath' => '../dsData',
            	),
        	));
        	if (strlen($alias)) {
        		Ac_Util::ms($prototypes, array(
	            	'dsData' => array(
	            		'alias' => $alias,
        				'debug' => true,
	            	),
        		));
        	}
        } 
    }
    
    function handleDsDataOnCurrentRecord() {
    	if (($b = $this->getControl('btnOpenDetails')))
    		$b->setDisabled(! $this->dsData->getCurrentRecord());
    }
    
    function handleDsDataOnDeleteRecord($dataSource, $eventType, $params) {
    	$this->triggerEvent(Pwg_I_RecordList::evtDeleteRecord, $params);
    }
    
    function handleBtnOpenDetailsClick() {
    	if ($r = $this->dsData->getCurrentRecord()) {
    		$this->triggerEvent(Pwg_I_RecordList::evtOpenDetails, array('primaryKey' => $r->getPrimaryKey(), 'record' => $r, 'mapperClass' => $this->getMapperClass()));
    	}
    }
    
    function handleBtnCreateClick() {
    	$this->triggerEvent(Pwg_I_RecordList::evtCreateRecord, array('mapperClass' => $this->getMapperClass()));
    } 	
	
    protected function getDataFieldName($column) {
        $res = $column->getFieldName();
        return $res;
    }
    
    protected function getDataFieldName($column) {
        $res = $column->getFieldName();
        return $res;
    }
    
    protected function orderByColumn(Pwg_Table_Column $column, $asc = true) {
        $fieldName = $this->getDataFieldName($column);       
        
        $fnd = $this->fltFilter->getFinder();
        $res = false;
        if ($fieldName && strlen($this->anySortCriterionName) && in_array($this->anySortCriterionName, $fnd->listCriteria())) {
        	$crit = $fnd->getCriterion($this->anySortCriterionName);
        	if (isset($this->colToPropMap[$fieldName])) {
                $c2p = $fieldName = $this->colToPropMap[$fieldName];
            } else {
                $c2p = false;
            }
        	if ($c2p || $crit->canSortByProperty($fieldName)) {
        		$crit->setValue(array('propName' => $fieldName, 'direction' => $asc));
        		$this->fltFilter->apply();
        		$res = true;
        	}
        }
        return $res;
    }
    
    function handleTblListColumnSortRequest(Pwg_Table $table, $eventType, $params) {
    	$params['setNewSort'] = $this->orderByColumn($params['column'], $params['sortIsAsc']);
    }
    
    protected function doAfterControlsCreated() {
    	parent::doAfterControlsCreated();
    	
    	$col = false;
    	if ($this->defaultOrderingColName) {
    		$colNames = $this->tblList->getColset()->listControls();
    		if ($this->defaultOrderingColName === true) $this->defaultOrderingColName = 0;
    		if (is_int($this->defaultOrderingColName)) {
    			$c = array_slice($colNames, $this->defaultOrderingColName, 1);
    			if (count($c)) $col = $c[0];
    		} else {
    			$col = $this->defaultOrderingColName;
    		}
    		if (in_array($col, $colNames)) {
    			$column = $this->tblList->getColset()->getControl($col);
    			if ($column->getSortable() && $this->orderByColumn($column, $this->defaultOrderingIsAsc)) {
    				$this->tblList->setSortMode(array($col, $this->defaultOrderingIsAsc));
    			}
    		} 
    	}
    }
    
    function handleTxtFilterChange(Pwg_Text $txtFilter) {
    	$fnd = $this->fltFilter->getFinder();
    	if (strlen($this->anySubstringCriterionName) && in_array($this->anySubstringCriterionName, $fnd->listCriteria())) {
    		$crit = $fnd->getCriterion($this->anySubstringCriterionName);
    		$text = $txtFilter->getText();
    		$cols = Pwg_Composite::findControlChildrenByProperties($this->tblList->getColset(), array('hidden' => false, 'searchable' => true), 'Pwg_Table_Column', false);
    		$props = array();
            $propsWithMap = array();
    		foreach ($cols as $col) {
                $fn = $this->getDataFieldName($col);
                if (strlen($fn)) {
                    if (isset($this->colToPropMap[$fn])) {
                        $propsWithMap[] = $this->colToPropMap[$fn];
                    } else {
                        $props[] = $fn;
                    }
                }
            }
            Pwg_Conversation::log("Initial search is ", $props);
    		$props = array_diff($props, $up = $crit->listUnsearchableProperties($props));
            Pwg_Conversation::log("uns is", $up);
            $props = array_merge($props, $propsWithMap);
    		if (!count($props) || !strlen($text)) $crit->setValue(null);
    			else $crit->setValue(array('propNames' => $props, 'substring' => $text));
    		$this->fltFilter->apply();
    	}
    }

    function handleTblListRowDblClick(Pwg_Table $tbl, $et, $params) {
        if (isset($params['row']) && $params['row'] instanceof Pwg_Table_Row) {
            $rec = $params['row']->getRecord();
            if ($rec instanceof Pwg_Record_Ae) {
                $r = $rec->getAeModelData();
                $this->triggerEvent(Pwg_I_RecordList::evtOpenDetails, array('primaryKey' => $r->getPrimaryKey(), 'record' => $r, 'mapperClass' => $this->getMapperClass()));
            }
        }
    }
    
}
