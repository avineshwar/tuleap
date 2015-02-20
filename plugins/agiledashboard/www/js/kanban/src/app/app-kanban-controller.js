(function () {
    angular
        .module('kanban')
        .controller('KanbanCtrl', KanbanCtrl);

    KanbanCtrl.$inject = [
        'SharedPropertiesService',
        'KanbanService',
        'CardFieldsService'
    ];

    function KanbanCtrl(SharedPropertiesService, KanbanService, CardFieldsService) {
        var self   = this,
            limit  = 50,
            offset = 0,
            kanban = SharedPropertiesService.getKanban();

        self.name      = kanban.name;
        self.nb_open   = kanban.nb_open;
        self.nb_closed = kanban.nb_closed;
        self.board = {
            columns: []
        };
        self.backlog = {
            content: [],
            label: 'Backlog',
            is_open: false,
            loading_items: true
        };
        self.archive = {
            content: [],
            label: 'Closed',
            is_open: false,
            loading_items: true
        };
        self.cardFieldIsSimpleValue       = CardFieldsService.cardFieldIsSimpleValue;
        self.cardFieldIsList              = CardFieldsService.cardFieldIsList;
        self.cardFieldIsText              = CardFieldsService.cardFieldIsText;
        self.cardFieldIsDate              = CardFieldsService.cardFieldIsDate;
        self.cardFieldIsFile              = CardFieldsService.cardFieldIsFile;
        self.cardFieldIsCross             = CardFieldsService.cardFieldIsCross;
        self.cardFieldIsPermissions       = CardFieldsService.cardFieldIsPermissions;
        self.getCardFieldListValues       = CardFieldsService.getCardFieldListValues;
        self.getCardFieldTextValue        = CardFieldsService.getCardFieldTextValue;
        self.getCardFieldFileValue        = CardFieldsService.getCardFieldFileValue;
        self.getCardFieldCrossValue       = CardFieldsService.getCardFieldCrossValue;
        self.getCardFieldPermissionsValue = CardFieldsService.getCardFieldPermissionsValue;

        loadColumns();
        loadBacklog(limit, offset);
        loadArchive(limit, offset);

        self.treeOptionsBacklog = {
            dropped: droppedInBacklog
        };

        self.treeOptionsArchive = {
            dropped: droppedInArchive
        };

        self.treeOptions = function (column) {
            return {
                dropped: function (event) {
                    return droppedInColumn(column, event);
                }
            };
        };

        function droppedInBacklog(event) {
            if (isDroppedInSameColumn(event)) {
                var dropped_item_id = event.source.nodeScope.$modelValue.id,
                    compared_to     = defineComparedTo(event.dest.nodesScope.$modelValue, event.dest.index);

                if (compared_to) {
                    KanbanService.reorderBacklog(kanban.id, dropped_item_id, compared_to);
                }
            }
        }

        function droppedInArchive() {
            // Archive does not let us to reorder things
        }

        function droppedInColumn(column, event) {
            if (isDroppedInSameColumn(event)) {
                var dropped_item_id = event.source.nodeScope.$modelValue.id,
                    compared_to     = defineComparedTo(event.dest.nodesScope.$modelValue, event.dest.index);

                if (compared_to) {
                    KanbanService.reorderColumn(kanban.id, column.id, dropped_item_id, compared_to);
                }
            }
        }

        function defineComparedTo(item_list, index) {
            var compared_to = {};

            if (item_list.length === 1) {
                return null;
            }

            if (index === 0) {
                compared_to.direction = 'before';
                compared_to.item_id   = item_list[index + 1].id;

                return compared_to;
            }

            compared_to.direction = 'after';
            compared_to.item_id   = item_list[index - 1].id;

            return compared_to;
        }

        function isDroppedInSameColumn(event) {
            return event.source.nodesScope.$id === event.dest.nodesScope.$id;
        }

        function loadColumns() {
            KanbanService.getKanban(kanban.id).then(function (kanban) {
                kanban.columns.forEach(function (column) {
                    column.content       = [];
                    column.loading_items = true;
                    loadColumnContent(column, limit, offset);
                });
                self.board.columns = kanban.columns;
            });
        }

        function loadColumnContent(column, limit, offset) {
            return KanbanService.getItems(kanban.id, column.id, limit, offset).then(function(data) {
                column.content = column.content.concat(data.results);

                if (offset + limit < data.total) {
                    loadColumnContent(column, limit, offset + limit);
                } else {
                    column.loading_items = false;
                }
            });
        }

        function loadBacklog(limit, offset) {
            return KanbanService.getBacklog(kanban.id, limit, offset).then(function(data) {
                self.backlog.content = self.backlog.content.concat(data.results);

                if (offset + limit < data.total) {
                    loadBacklog(limit, offset + limit);
                } else {
                    self.backlog.loading_items = false;
                }
            });
        }

        function loadArchive(limit, offset) {
            return KanbanService.getArchive(kanban.id, limit, offset).then(function(data) {
                self.archive.content = self.archive.content.concat(data.results);

                if (offset + limit < data.total) {
                    loadArchive(limit, offset + limit);
                } else {
                    self.archive.loading_items = false;
                }
            });
        }
    }
})();
