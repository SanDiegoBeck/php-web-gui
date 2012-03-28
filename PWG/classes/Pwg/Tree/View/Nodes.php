<?php

class Pwg_Tree_View_Nodes extends Pwg_Tree_View {
    
    /**
     * don't check whether parent of refreshed node had changed
     */
    const TM_DONT_TRACK_MOVEMENT = 0;
    
    /**
     * node is moved to new parent only if that parent is shown; otherwise it's hidden
     */
    const TM_DONT_FORCE_VISIBILITY = 1;
    
    /**
     * if node has moved to invisible/unloaded ancestors, it will be shown (as in Pwg_Tree_View_Nodes::showNodes() with $withAncestors set to TRUE)
     */
    const TM_FORCE_VISIBILITY = 2;

    /**
     * Don't load children
     */
    const POPULATE_NOTHING = 0;
    
    /**
     * Add stub nodes if $this->lazyLoad is true, otherwise recursively load children
     */ 
    const POPULATE_WITH_STUBS_IF_LAZY_OTHERWISE_RECURSIVE = 1;
    
    /**
     * Recursively load children
     */ 
    const POPULATE_RECURSIVE = 2;
    
    /**
     * Load only immediate children
     */
    const POPULATE_DIRECT_CHILDREN_ONLY = 3;
    
    /**
     * @var Pwg_I_Tree_Provider
     */
    protected $treeProvider = false;

    /**
     * @var Pwg_I_Tree_Node
     */
    protected $currentNode = false;
    
    protected $visibleNodes = array();
    
    protected $lazyLoad = false;

    protected $lazyLoadLabel = 'Loading...';

    protected $loadContainersForShownNodes = true;

    protected $loadCountsForShownNodes = true;
    
    protected $loadChildrenForShownNodes = true;
    
    protected $showSiblingsLabel = 'Show other nodes >>';
    
    protected $treeNodePrototype = array();

    protected $oldClass = false;
    
    protected $selectedClass = 'currentNode';
    
    protected $setCurrentNodeOnClick = false;
    
    protected $alwaysLoadChildrenOnExpand = false;
    
    protected $withCheckboxes = false;
        
    protected function doOnInitialize(array $options = array()) {
        $this->observeChildClicks = true;
        $this->observeChildExpand = true;
        $res = parent::doOnInitialize($options);
        return $res;
    }
    
    function setTreeProvider(Pwg_I_Tree_Provider $treeProvider) {
        if ($this->treeProvider && ($this->treeProvider !== $treeProvider))
            throw new Exception("Can set \$treeProvider only once");
            
        $this->treeProvider = $treeProvider;
    }

    /**
     * @return Pwg_I_Tree_Provider
     */
    function getTreeProvider() {
        return $this->treeProvider;
    }     
    
    function setCurrentNode($nodeOrId, $scrollIntoView = false) {
        $noi = array_values($this->extractNodes($nodeOrId, true, true));
        $oldCurrentNode = false;
        $oldVisualNode = false;
        $currentNode = false;
        $visualNode = false;
        $this->oldClass = '';
        if (count($noi) && $noi[0]) $currentNode = $noi[0];
            else $currentNode = false;
        if ($currentNode !== ($oldCurrentNode = $this->currentNode)) {
            if ($oldCurrentNode && $oldVisualNode = $this->findTreeNode($oldCurrentNode)) {
                $oldVisualNode->setClassName($this->oldClass);
            }
            $this->currentNode = $currentNode;
            if ($currentNode) {
                $this->showNodes($currentNode, true, self::POPULATE_WITH_STUBS_IF_LAZY_OTHERWISE_RECURSIVE, true);
                if ($visualNode = $this->findTreeNode($currentNode)) {
                    $this->oldClass = (string) $visualNode->getClassName();
                    $visualNode->setClassName(((string) $visualNode->getClassName()).' '.$this->selectedClass);
                    if ($scrollIntoView) $visualNode->scrollIntoView();
                }
            } else {
            }
            $this->triggerEvent('currentNodeChange', array(
                'oldCurrentNode' => $oldCurrentNode,
                'oldVisualNode' => $oldVisualNode,
                'currentNode' => $currentNode,
                'visualNode' => $visualNode,
            ));
        }
    }

    /**
     * @return Pwg_I_Tree_Node
     */
    function getCurrentNode() {
        return $this->currentNode;
    }

    /**
     * Loads given data node(s) if they are not loaded; shows them up.  
     * @param $nodeOrNodesOrIds Pwg_I_Tree_Node instance | node id | array of Pwg_I_Tree_Node instances and/or node ids
     * @param bool $withAncestors Whether to load and show required ancestors (for nodes that otherwise won't be shown) 
     * @param bool $expandAncestors  Whether to make branches of respective nodes expanded (to make these nodes visible for the user)
     * @param int $populateChildren One of self::POPULATE_* constants
     * 
     * $populateChildren parameter can have following values:
     *  - self::POPULATE_NOTHING - Don't load children;
     *  - self::POPULATE_WITH_STUBS_IF_LAZY_OTHERWISE_RECURSIVE - Add stub nodes if $this->lazyLoad is true, otherwise recursively load children;
     *  - self::POPULATE_RECURSIVE - Recursively load children;
     *  - self::POPULATE_DIRECT_CHILDREN_ONLY - Load only immediate children
     */
    function showNodes($nodeOrNodesOrIds, $withAncestors = true, $populateChildren = self::POPULATE_WITH_STUBS_IF_LAZY_OTHERWISE_RECURSIVE, $expandAncestors = true) {
        $nodes = $this->getLoadedNodes($nodeOrNodesOrIds);
        $ids = array();
        foreach ($nodes as $node) {
        	$ids[] = $node->getNodeId();
            $p = $this->findTreeParentOfDataNode($node);
            if (!$p && $withAncestors) $p = $this->createAncestorsBranch($node);
            //if (!p) throw new Exception("Assertion: we should have node parent at this point", E_USER_ERROR);
            if ($p) {
                if (!$p instanceof Pwg_Tree_Parent) throw new Exception("Some shit is provided instead of Pwg_Tree_Parent: ".$p);
                $tn = $this->createTreeNode($node, $populateChildren, $p);
                $tn->expandAncestors();
            } else {
            }
        }
    }
    
    /**
     * Hides given data node(s) and removes corresponding Pwg_Tree_Node's from the tree 
     * @param $nodeOrNodesOrIds Pwg_I_Tree_Node instance | node id | array of Pwg_I_Tree_Node instances and/or node ids 
     */
    function hideNodes($nodeOrNodesOrIds) {
        $nodes = array_keys($this->extractNodes($nodeOrNodesOrIds, false, false));
        foreach ($nodeIds as $id => $node) {
            if ($treeNode = $this->findTreeNode($node)) $treeNode->destroy();
        }
    }
    
    /**
     * @param $nodeOrNodesOrIds Pwg_I_Tree_Node instance | node id | array of Pwg_I_Tree_Node instances and/or node ids
     * @param bool $withChildren Whether node children should also be recursively refreshed
     * @param int $trackMovement - one of Pwg_Tree_View_Nodes::DONT_TRACK_MOVEMENT | Pwg_Tree_View_Nodes::DONT_FORCE_VISIBILITY | Pwg_Tree_View_Nodes::FORCE_VISIBILITY constants
     * @param bool $forceNewNodesDisplay Whether to display (with ancestors) nodes that are in $nodeOrNodesOrIds but currenlty not loaded/shown  
     * 
     * Here's how $trackMovement works:
     * - Pwg_Tree_View_Nodes::TM_DONT_TRACK_MOVEMENT - don't check whether parent of refreshed node had changed
     * - Pwg_Tree_View_Nodes::TM_DONT_FORCE_VISIBILITY - node is moved to new parent only if that parent is shown; otherwise it's hidden
     * - Pwg_Tree_View_Nodes::TM_FORCE_VISIBILITY - if node has moved to invisible/unloaded ancestors, it will be shown (as in Pwg_Tree_View_Nodes::showNodes() with $withAncestors set to TRUE) 
     */
    function refreshNodes($nodeOrNodesOrIds, $withChildren = true, $trackMovement = self::TM_DONT_FORCE_VISIBILITY, $forceNewNodesDisplay = false) {
        $nodes = $this->extractNodes($nodeOrNodesOrIds, $forceNewNodesDisplay, $forceNewNodesDisplay);
        foreach ($nodes as $node)
            if ($node)
                $this->refreshNode($node, $withChildren, $trackMovement, $forceNewNodesDisplay);
    }
    
    function setSetCurrentNodeOnClick($setCurrentNodeOnClick) {
        $this->setCurrentNodeOnClick = $setCurrentNodeOnClick;
    }

    function getSetCurrentNodeOnClick() {
        return $this->setCurrentNodeOnClick;
    }

    function setCheckedNodes($nodeOrNodesOrIds) {
        if (!$this->withCheckboxes) {
            trigger_error (__FUNCTION__."() makes sense only for Pwg_Tree_View_Nodes with useCheckboxes set to true", E_USER_NOTICE);
            return;
        } else {
            $items = $this->findChildrenByProperty('checked', true, 'Pwg_Tree_Node_Toggle', true, true);
            $this->setProperty($items, 'checked', false);
            $nodes = $this->getLoadedNodes($nodeOrNodesOrIds, true);
            $this->showNodes($nodes, true);
            foreach ($nodes as $dataNode) {
                if ($visualNode = $this->findTreeNode($dataNode)) $visualNode->setChecked(true);
            }
        }
    }
    
    function getCheckedNodes() {
        $res = array();
        if (!$this->withCheckboxes) {
            trigger_error (__FUNCTION__."() makes sense only for Pwg_Tree_View_Nodes with useCheckboxes set to true", E_USER_NOTICE);
        } else {
            $items = $this->findChildrenByProperty('checked', true, 'Pwg_Tree_Node_Toggle', true, true);
            foreach ($items as $item) {
                if ($node = $this->getDataNode($tem)) {
                    $res[$node->getNodeId()] = $node;
                }
            }
        }
        return $res;
    }
    
    function getCheckedIds() {
        $res = array();
        if (!$this->withCheckboxes) {
            trigger_error (__FUNCTION__."() makes sense only for Pwg_Tree_View_Nodes with useCheckboxes set to true", E_USER_NOTICE);
        } else {
            $items = $this->findChildrenByProperty('checked', true, 'Pwg_Tree_Node_Toggle', true, true);
            foreach ($items as $item) {
                $res[] = $item->getData();
            }
        }
        return $res;
    }
    
    /**
     * @return Pwg_Tree_Node
     */
    function findTreeNodeByDataNode($dataNodeOrId) {
        if ($dataNodeOrId instanceof Pwg_I_Tree_Node) $id = $dataNodeOrId->getNodeId();
            else $id = $dataNodeOrId;
        $res = $this->getFirstNode($this->findChildrenByProperty('data', $id), false, true);
        return $res;
    }
    
    // +-------------- init-time properties --------------+
    
    protected function refreshNode(Pwg_I_Tree_Node $node, $withChildren, $trackMovement, $forceNewNodesDisplay) {
        if (!($visualNode = $this->findTreeNode($node))) $this->showNodes(array($node), true);
        else {
            $currentVisualParent = $visualNode->getParent();
            $requiredVisualParent = $this->findTreeParentOfDataNode($node);
            if ($currentVisualParent !== $requiredVisualParent) {
                if (!$requiredVisualParent && $trackMovement === self::TM_FORCE_VISIBILITY)
                    $requiredVisualParent = $this->createAncestorsBranch($node);
                if ($requiredVisualParent) $visualNode->changeParent($requiredVisualParent);
            }
            if ($withChildren) {
                $this->refreshNodes($node->listChildNodes()  , $withChildren, $trackMovement, $forceNewNodesDisplay);
            }
        }
    }
    
    protected function setLazyLoad($lazyLoad) {
        $this->lazyLoad = $lazyLoad;
    }

    function getLazyLoad() {
        return $this->lazyLoad;
    }

    protected function setLazyLoadLabel($lazyLoadLabel) {
        $this->lazyLoadLabel = $lazyLoadLabel;
    }

    function getLazyLoadLabel() {
        return $this->lazyLoadLabel;
    }    

    protected function setShowSiblingsLabel($showSiblingsLabel) {
        $this->showSiblingsLabel = $showSiblingsLabel;
    }

    function getShowSiblingsLabel() {
        return $this->showSiblingsLabel;
    }    

    function setTreeNodePrototype(array $treeNodePrototype) {
        $this->treeNodePrototype = $treeNodePrototype;
    }

    function getTreeNodePrototype() {
        return $this->treeNodePrototype;
    }

    /**
     * Whether to use Pwg_Tree_Node_Toggle nodes
     * @param bool $withCheckboxes
     */
    protected function setWithCheckboxes($withCheckboxes) {
        $this->withCheckboxes = (bool) $withCheckboxes;
    }

    function getWithCheckboxes() {
        return $this->withCheckboxes;
    }
    
    // +-------------- internal stuff --------------+

    /**
     * @param Pwg_Tree_Node $visualNode
     * @param bool $load Load data node if it's not loaded
     * @return Pwg_I_Tree_Node
     */
    protected function getDataNode(Pwg_Tree_Node $visualNode, $load = true) {
        $id = $visualNode->getData();
        if (strlen($id) && $id !== '__loadingStub' && $id !== '__showChildrenStub') {
            $res = $this->treeProvider->getNode($id, $load);
        } else {
            $res = null; 
        }
        return $res;
    }
    
    protected function extractNodes($nodeOrNodesOrIdOrIds, $load = true, $register = true) {
        $p = $nodeOrNodesOrIdOrIds;
        if (!is_array($p)) $p = array($p);
        $ids = array();
        $allIds = array();
        $nodes = array();
        foreach ($p as $k => $item) {
            if ($item instanceof Pwg_I_Tree_Node) {
                if (!strlen($id = $item->getNodeId())) throw new Exception("Currently can't use nodes without IDs");
                $nodes[] = $item;
                $allIds[$k] = $id;   
            } else {
                $ids[] = $item;
                $allIds[$k] = $item;
            } 
        }
        if ($ids && $load) $this->treeProvider->loadNodes($ids);
        if ($nodes && $register) $this->treeProvider->registerNodes($nodes);
        $res = array();
        foreach ($allIds as $id) {
            $node = $this->treeProvider->getNode($id, false);
            $res[$id] = $node? $node : false;
        }
        return $res;
    }
    
    protected function getLoadedNodes($nodeOrNodesOrIdOrIds, $returnOnlyNodesThatLoadedSuccessfully = true) {
        $nodes = $this->extractNodes($nodeOrNodesOrIdOrIds, true, true);
        if ($this->loadContainersForShownNodes && $this->treeProvider)
            $this->treeProvider->loadContainers(array_keys($nodes));
        if ($this->loadCountsForShownNodes && $this->treeProvider)
            $this->treeProvider->loadChildNodeCounts(array_keys($nodes));
        if ($returnOnlyNodesThatLoadedSuccessfully) {
            $res = array();
            foreach ($nodes as $k => $n) if ($n) $res[$k] = $n;
        } else {
            $res = $nodes;
        }
        return $res;
    }
    
    protected function getNodeId(Pwg_I_Tree_Node $dataNode) {
        $res = $dataNode->getNodeId();
        if (!strlen($res)) throw new Exception("Currently nodes without ID are not supported");
        return $res;
    }
    
    /**
     * @param array $nodes
     * @param $default
     * @param $assertIfMoreThanOne
     * @return Pwg_Tree_Node
     */
    protected function getFirstNode(array $nodes, $default = false, $assertIfMoreThanOne = false) {
        if ($c = count($vs = array_values($nodes))) {
            if ($assertIfMoreThanOne && ($c > 1)) throw new Exception("Assertion: it cannot be more than one node in \$nodes array");
            $res = $vs[0];
        } else $res = $default;
        return $res;
    }
    
    /**
     * @param Pwg_I_Tree_Node $dataNode
     * @return Pwg_Tree_Node
     */
    protected function findTreeNode(Pwg_I_Tree_Node $dataNode) {
        return $this->getFirstNode($this->findChildrenByProperty('data', $this->getNodeId($dataNode)), false, true);
    }

    /**
     * @param Pwg_I_Tree_Node $dataNode
     * @return Pwg_Tree_Parent
     */
    protected function findTreeParentOfDataNode(Pwg_I_Tree_Node $dataNode) {
        $pId = $dataNode->getParentNodeId();
        if (is_null($pId) || ((string) $pId === '0')) $res = $this;
        else {
            $res = $this->getFirstNode($this->findChildrenByProperty('data', $pId), false, true);
        }
        return $res;
    }
    
    /**
     * @param Pwg_I_Tree_Node $dataNode
     * @param int $alsoInitializeChildren (self::POPULATE_* constants)
     * @param Pwg_Tree_Parent $parent  
     * @return Pwg_Tree_Node
     */
    protected function createTreeNode(Pwg_I_Tree_Node $dataNode, $alsoInitializeChildren = self::POPULATE_NOTHING, Pwg_Tree_Parent $parent = null) {
        if (!($res = $this->findTreeNode($dataNode))) {
            $prototype = $this->treeNodePrototype;
            $prototype['displayOrder'] = $dataNode->getOrdering();
            $prototype['data'] = $this->getNodeId($dataNode);
            $this->triggerEvent('onCreateTreeNode', array('prototype' => & $prototype, 'dataNode' => $dataNode, 'res' => & $res));
            if (!$res) {
                if (!isset($prototype['content'])) $prototype['content'] = $dataNode->getTitle();
                $prototype['triggerEventOnSoftExpand'] = true; 
                $res = Pwg_Base::factory($prototype, $this->withCheckboxes? 'Pwg_Tree_Node_Toggle' : 'Pwg_Tree_Node');
            }
            switch ($alsoInitializeChildren) {
                case self::POPULATE_NOTHING: break;
                
                case self::POPULATE_WITH_STUBS_IF_LAZY_OTHERWISE_RECURSIVE:
                    if ($this->lazyLoad) $this->populateWithStubs($res);
                        else $this->loadAndShowChildren($res, self::POPULATE_RECURSIVE);
                    break;
                case self::POPULATE_DIRECT_CHILDREN_ONLY:
                    $this->loadAndShowChildren($res, self::POPULATE_NOTHING);
                    break;
                
                case self::POPULATE_RECURSIVE:
                    $this->loadAndShowChildren($res, self::POPULATE_RECURSIVE);
                    break;
                    
                default: throw new Exception("Unknown \$alsoInitializeChildren value; should be one of Pwg_Tree_View_Nodes::POPULATE_* constants");
            }
            if (!is_null($parent)) $parent->addControl($res);
        }
        return $res;
    }
    
    protected function populateWithStubs(Pwg_Tree_Node $node) {
        $data = $this->getDataNode($node);
        if ($data->getChildNodesCount()) {
            $nd = false;
            if (!$node->listControls()) $node->addControl($nd = new Pwg_Tree_Node(array(
                'data' => '__loadingStub',
                'content' => $this->lazyLoadLabel,
            ))); else $node->addControl(new Pwg_Tree_Node($nd = array(
                'data' => '__showChildrenStub',
                'content' => $this->showSiblingsLabel,
            )));
            if ($node->isFrontInitialized() && $nd) $nd->initializeFront();
        }
    }
    
    protected function isLoadingStub(Pwg_Tree_Node $node) {
        return $node->getData() === '__loadingStub';
    }
    
    protected function isShowChildrenStub(Pwg_Tree_Node $node) {
        return $node->getData() === '__showChildrenStub';
    }
    
    /**
     * @return Pwg_Tree_Node
     */
    protected function findLoadingStub(Pwg_Tree_Node $node) {
        $stubs = $node->findChildrenByProperty('data', '__loadingStub', false, true, false);
        if (count($stubs)) $res = $stubs[0];
            else $res = false;
        return $res;
    }
    
    /**
     * @return Pwg_Tree_Node
     */
    protected function findShowChildrenStub(Pwg_Tree_Node $node) {
        $stubs = $node->findChildrenByProperty('data', '__showChildrenStub', false, true, false);
        if (count($stubs)) $res = $stubs[0];
            else $res = false;
        return $res;
    }
    
    protected function loadAndShowChildren(Pwg_Tree_Node $visualNode, $childInitMode) {
        $dataNode = $this->getDataNode($visualNode, true);
        $childNodes = $this->getLoadedNodes($dataNode->listChildNodes());
        foreach ($childNodes as $childDataNode) {
            $this->createTreeNode($childDataNode, $childInitMode, $visualNode);
        }
    }
    
    /**
     * Creates (if they are not already created) nodes for all ancestors of given node
     * Returns node's parent.
     * 
     * @param Pwg_I_Tree_Node $dataNode
     * @return Pwg_Tree_Node
     */
    protected function createAncestorsBranch(Pwg_I_Tree_Node $dataNode) {
        $allAncestorIdsFromTopToBottom = array_reverse($dataNode->getAllParentNodeIds(false));
        $ancestors = $this->getLoadedNodes($allAncestorIdsFromTopToBottom);
        $parent = $this;
        foreach ($ancestors as $id => $dataNode) {
            if (!($treeNode = $this->findTreeNode($dataNode))) {
                $parent = $this->createTreeNode($dataNode, self::POPULATE_WITH_STUBS_IF_LAZY_OTHERWISE_RECURSIVE, $parent);
            } else {
                $parent = $treeNode;
            }
        }
        return $parent;
    }
    
    protected function doGetConstructorName() {
        return 'Pwg_Tree_View';
    }
    
    function notifyChildClick(Pwg_Tree_Node $child) {
        parent::notifyChildClick($child);
        if ($this->isShowChildrenStub($child)) {
            if ($node = $this->getDataNode($child->getParent(), false)) {
                 $this->showNodes($node->listChildNodes());
            }
            $child->destroy();
        } elseif ($this->setCurrentNodeOnClick && ($node = $this->getDataNode($child, false))) {
            $this->setCurrentNode($node);
        }
    }
    
    function notifyChildExpand(Pwg_Tree_Node $child, $byUser) {
        parent::notifyChildExpand($child);
        if ($byUser || $this->alwaysLoadChildrenOnExpand) {
            if ($this->lazyLoad && ($ls = $this->findLoadingStub($child))) {
                $ls->destroy();
                if ($node = $this->getDataNode($child, false)) {
                    $this->showNodes($node->listChildNodes());
                }
            }
        }
    }
    
    function setAlwaysLoadChildrenOnExpand($alwaysLoadChildrenOnExpand) {
        $this->alwaysLoadChildrenOnExpand = $alwaysLoadChildrenOnExpand;
    }

    function getAlwaysLoadChildrenOnExpand() {
        return $this->alwaysLoadChildrenOnExpand;
    }

    function notifyNodeDestroyed(Pwg_Tree_Node $node) {
        parent::notifyNodeDestroyed($node);
        if ($this->currentNode && ($data = $this->getDataNode($node))) {
            $nId = $data->getNodeId();
            if ($nId) {
                if ($nId == $this->currentNode->getNodeId()) $this->currentNode = false;
            }
            $data->destroy();
        }
    }
    
    protected function doListPassthroughParams() {
        return array_merge(parent::doListPassthroughParams(), array('currentNodeId'));
    }
    
    protected function jsGetCurrentNodeId() {
        $res = false;
        if ($this->currentNode) {
            $tn = $this->findTreeNode($this->currentNode);
            if ($tn) $res = $tn->getResponderId();  
        }
        return $res; 
    }
    
//    function clear() {
//        parent::clear();
//        if ($this->treeProvider) $this->treeProvider->clearAllNodes();
//    }    
    
}

?>