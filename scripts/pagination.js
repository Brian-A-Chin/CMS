//import {append} from "../3rdParty/grapesjs/src/utils/dom";

var dateColumns = [];

$(window).on("load", function () {

    if (!$('.pagination-table').length){return false}
    var configuration = {
        searchQueryParam: 'search',
        sortParam: 'sort',
        sortDelimiter: ':',
        categoryParam: 'category',
        pagingParam: 'currentPage',
        queryLimitParam: 'pageSize',
        queryLimitVal: '10',
        startDateParam: 'startDate',
        endDateParam: 'endDate',
        mainSearchId: 'main-search',
        dateColumn: 'dateColumn',
    }
    var dependentOutput = '';
    var currentSort = {};
    var currentPageNumber = Core.urlObject.get(configuration.pagingParam) !== null ? Core.numberFormat(Core.urlObject.get(configuration.pagingParam)) : -1;



    var RangeStart = Core.urlObject.get(configuration.startDateParam) === null ? moment().subtract(29, 'days') : moment(Core.urlObject.get(configuration.startDateParam));
    var RangeEnd = Core.urlObject.get(configuration.endDateParam) === null ? moment() : moment(Core.urlObject.get(configuration.endDateParam));

    var defaultRangePeriod = $('.rangepicker').attr('data-default-range') != undefined ? $('.rangepicker').attr('data-default-range') : 'All Time';

    var pagingParameters = {};

    function setSelect(data) {
        $('.urlTuner').each(function(){
            var value = this.id;
            var result = Core.urlObject.get(value);
            if (result !== null && $('#' + value + ' option[value="' + result + '"]').length > 0) {
                $('#' + value).val(result);
            }
        });
    }



    function getSortClass(sort) {
        return sort === 'DESC' ? '<i class="fas fa-angle-down sort-i" aria-hidden="true"></i>' : '<i class="fas fa-angle-up sort-i" aria-hidden="true"></i>';
    }


    function initSorting() {
        var sortColumns = Core.urlObject.get(configuration.sortParam);
        if(sortColumns == null || sortColumns == undefined)
            sortColumns = [];
        else
            sortColumns = sortColumns.split(configuration.sortDelimiter);

        if (Core.urlObject.get(configuration.sortParam) !== null) {
            var i = 0;
            $.each(sortColumns, function (k, v) {
                if (i % 2 === 0) {
                    var $this = $('#' + v);

                    var sort = sortColumns[k + 1];
                    currentSort[v] = sort;
                    $this.attr('data-sort', sort);
                    $this.append(getSortClass(sort));
                    $this.append('<i class="fas fa-times-circle delsort-i" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Omit ' + v + ' from sorting"></i>');
                    if (i === 0) {
                        $('.table-responsive').prepend(`<small id="sortPriority">Sorting on: <span data-toggle="tooltip" data-placement="top" title="Click to omit. Drag to reorder" data-value="${v}">${$this.text()}</span></small>`);
                        $this.css({ 'background-color': '#d9534f', 'color': '#fff' });
                        $this.attr('data-primary-sort','true');
                    } else {
                        $('#sortPriority').append(`<span data-toggle="tooltip" data-placement="top" title="Click to omit. Drag to reorder" data-value="${v}">${$this.text()}</span>`);
                    }

                }
                i++;
            });
        }
        $.each($('.setSort'), function () {
            var primarySortAttr = $(this).attr('data-primary-sort');
            var isPrimarySort = typeof primarySortAttr !== 'undefined' && primarySortAttr !== false;
            var isSortingOn = $.inArray($(this).attr('data-sort'),sortColumns) != -1;
            if ($(this).attr('data-sort').length && !currentSort.hasOwnProperty(this.id) && isSortingOn) {
                var sort = $(this).attr('data-sort').toUpperCase();
                currentSort[this.id] = sort;
                $(this).append(getSortClass(sort));
                $(this).append('<i class="fas fa-times-circle delsort-i" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Omit from sorting"></i>');
            }else if(!isPrimarySort && !isSortingOn){
                $(this).prepend('<i class="fas fa-sort"></i> ');
            }
        });

    }

    function setSort(params) {
        var dependentDelimiter = Object.keys(currentSort).length > 0 && params.override ? configuration.sortDelimiter : '';
        var sortParam = configuration.sortParam;
        var target = params.target;
        var value = params.value;
        if (Object.keys(currentSort).length > 0) {
            if (params.action === 'set') {
                if (params.override) {
                    delete currentSort[target];
                    dependentOutput = target + configuration.sortDelimiter + value;
                }
                else {
                    currentSort[target] = value;
                }

            }
            if (params.action === 'delete') {
                delete currentSort[target];
            }
            if (Object.keys(currentSort).length !== 0) {
                currentSort = jQuery.map(currentSort, function (t, v) {
                    return v + configuration.sortDelimiter + t;
                });
                Core.urlObject.set(sortParam, dependentOutput + dependentDelimiter + currentSort.join(configuration.sortDelimiter));
            }
            else {
                Core.urlObject.delete(sortParam);
            }
        }
        else {
            Core.urlObject.set(sortParam, target + configuration.sortDelimiter + value);
        }
        window.location.href = decodeURIComponent(Core.url)
    }
    function setSearch() {
        var searchValue = Core.urlObject.get(configuration.searchQueryParam);
        var searchDropDownCategory = Core.urlObject.get(configuration.categoryParam);
        if (Core.urlObject.get(configuration.searchQueryParam) || (Core.urlObject.get(configuration.startDateParam) && Core.urlObject.get(configuration.endDateParam))) {
            if (Core.urlObject.get(configuration.searchQueryParam) && $('#' + configuration.categoryParam+' option[value='+searchDropDownCategory+']').length)
                //set search input text box
                $('#' + configuration.mainSearchId).val(searchValue);
            //set search drop down
            if(searchDropDownCategory != null)
                $('#' + configuration.categoryParam).val(searchDropDownCategory);

            if (!$.trim($("table tbody").html())) {
                var response = searchValue !== null ? 'Your search - ' + searchValue.substr(0, 50) + ' - did not return any results. Did you mean to search by '+$('#' + configuration.categoryParam+' option[value='+searchDropDownCategory+']').text()+'?' : 'There are no records between the dates: ' + moment(Core.urlObject.get(configuration.startDateParam)).format("MMM Do Y") + ' and ' + moment(Core.urlObject.get(configuration.endDateParam)).format("MMM Do Y");
                $("table").after('<div class="alert alert-danger" role="alert">' + response + '</div >');
                $("table thead, .paging-dependent").hide();
            }
        }
    }

    function setdateColumns(){
        $(".pagination-table thead tr th").each(function () {
            var type = $(this).data('type');
            if (type!= undefined)
                type = type.trim();
            if ($.inArray(type, ['datetime', 'smalldatetime']) !== -1) {
                if (this.hasAttribute('data-alt-value')) {
                    dateColumns.push({
                        'displayName' : $(this).text(),
                        'value' : $(this).attr('data-alt-value'),
                    });
                } else {
                    dateColumns.push({
                        'displayName' : $(this).text(),
                        'value' : $(this).text().replace(/\s/g, ''),
                    });
                }
            }
        });
        if (Object.keys(dateColumns).length != 0) {
            if(Core.urlObject.get(configuration.dateColumn) != null){
                Core.urlObject.set(configuration.dateColumn, Core.urlObject.get(configuration.dateColumn));
            }else{
                Core.urlObject.set(configuration.dateColumn, dateColumns[0].value);
            }
            window.history.pushState(null, null, Core.url);
            var currentdateColumn = Core.urlObject.get(configuration.dateColumn);

            $.each(dateColumns, function (key, obj) {
                $('#dateColumn').append($('<option></option>').val(obj.value).text(obj.displayName));
            });

            if (currentdateColumn == null) {
                $('.dateRangeBtnContainer').hide();
            } else {
                setSelect();
                $('#rangeFilterMsg').text('=')
            }

            $('.dateColumnSelector').show();
        } else {
            $('.dateRangeBtnContainer').hide();
        }
    }





    function search() {
        var term = $('#' + configuration.mainSearchId).val();
        var queryString = configuration.searchQueryParam;
        var categoryParam = configuration.categoryParam;
        if (term.length > 0) {
            Core.urlObject.set(configuration.pagingParam, 1);
            Core.urlObject.set(categoryParam, $('#' + categoryParam).val());
            Core.urlObject.set(queryString, term);
        } else {
            Core.urlObject.delete(queryString);
            Core.urlObject.delete(categoryParam);
        }
        window.location.href = Core.url;
    }


    function initPaging(force) {
        var pagingParam = configuration.pagingParam;
        if ((Core.urlObject.get(pagingParam) === null || Core.numberFormat(Core.urlObject.get(pagingParam)).length === 0 || force) && !Core.exceptionMode) {
            Core.urlObject.set(pagingParam, 1);
            Core.urlObject.set(configuration.queryLimitParam, configuration.queryLimitVal);
            window.history.pushState(null, null, Core.url);
            currentPageNumber = Core.urlObject.get(configuration.pagingParam) !== null ? Core.numberFormat(Core.urlObject.get(configuration.pagingParam)) : -1;

        }
        //markPage();

        setdateColumns();

        setSelect();

    }

    function markPage() {

        if (currentPageNumber == undefined || currentPageNumber < 0) {
            initPaging(true)
        } else {
            $('#page-' + currentPageNumber).addClass('page-active');

            if (pagingParameters.totalPages == 1) {
                $('.pagnate,.page-jump').addClass('page-disabled');
            }
            if (pagingParameters.currentPage == 1) {
                $('[data-direction=pre]').addClass('page-disabled');
                $('.jump-to-start').addClass('page-disabled');
            } else if (pagingParameters.currentPage == pagingParameters.totalPages) {
                $('[data-direction=fwd]').addClass('page-disabled');
                $('.jump-to-end').addClass('page-disabled');
            }
        }

    }

    function goToPageNumber(pageNumber) {
        if (Core.numberFormat(Core.urlObject.get(configuration.pagingParam)).length !== 0) {
            Core.urlObject.set(configuration.pagingParam, pageNumber);
            window.location.href = Core.url;
        }
    }


    function rangeCB(RangeStart, RangeEnd) {
        if (Core.urlObject.get(configuration.startDateParam) !== null && Core.urlObject.get(configuration.endDateParam) !== null) {
            $('.rangepicker span').html(RangeStart.format('MMMM D, YYYY') + ' - ' + RangeEnd.format('MMMM D, YYYY'));
        }
        else {
            $('.rangepicker span').html(defaultRangePeriod);
        }
    }

    var rangeParams = {
        opens: 'auto',
        drops: 'up',
        startDate: RangeStart,
        endDate: RangeEnd,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],

        }
    };
    rangeParams.ranges[defaultRangePeriod] = [false, false];


    function reorderSort(startPosition, endPosition) {
        if (startPosition !== endPosition) {
            var newSort = {};
            $.each($('#sortPriority span'), function () {
                var key = $(this).attr('data-value').trim();
                newSort[key] = currentSort[key];
            });
            currentSort = newSort;
            console.log(currentSort)
            setSort({
                action: 'reorder'
            });
        }
    }

    initSorting();
    $("#sortPriority").sortable({
        placeholder: "sort-order-placeholder",
        start: function (event, ui) {
            ui.placeholder.width(ui.item.width());
            ui.item.toggleClass("sort-order-placeholder");
            startPosition = ui.item.index();
            ui.item.css({
                'color': 'red',
                'width': 'max-content'
            });
        },
        stop: function (event, ui) {
            ui.item.toggleClass("sort-order-placeholder");
            endPosition = ui.item.index();
            ui.item.removeAttr('style');
            reorderSort(startPosition, endPosition);
        }
    });

    setSearch();
    $('select option').each(function () {
        this.text = Core.replaceUnderscores(this.text)
    });

    $(document).on('keypress', function (e) {
        if (e.which == 13 && 1 == 4) {
            search();
        }
    });



    $('.rangepicker').on('show.daterangepicker', function (ev, picker) {
        $('.overlay').fadeIn();
    });

    $('.rangepicker').on('hide.daterangepicker', function (ev, picker) {
        $('.overlay').fadeOut();
    });

    $('.rangepicker').on('apply.daterangepicker', function (ev, picker) {
        if (picker.startDate.isValid()) {
            Core.urlObject.set(configuration.startDateParam, picker.startDate.format('YYYY-MM-DD'));
            Core.urlObject.set(configuration.endDateParam, picker.endDate.add(1, 'days').format('YYYY-MM-DD'));
        }
        else {
            $('.rangepicker span').html('All Time');
            Core.urlObject.delete(configuration.startDateParam);
            Core.urlObject.delete(configuration.endDateParam);
        }
        Core.urlObject.set(configuration.pagingParam, 1);
        window.location.href = Core.url;
    });

    $(document).on("mouseover", ".init-paging", function (event) {
        $(this).removeClass("init-paging");
        $(this).attr("href", $(this).attr("href") + '?' + configuration.pagingParam + '=1&' + configuration.queryLimitParam + '=' + configuration.queryLimitVal);
    });

    $(document).on("click", ".disabled, .page-disabled", function (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    });


    $(document).on("click", ".grouped-input-submit", function (event) {
        if ($(this).hasClass('stopPropagation')) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }
        $('.loader').fadeIn();
        var url = $(this).attr('data-url');
        var formData = {};
        $.each($('.' + $(this).attr('data-group')), function (key, value) {
            formData[value.getAttribute('name')] = value.value;
        });
        var reload = false;
        if ($(this)[0].hasAttribute("data-reload")) {
            reload = ($(this).attr("data-reload") === "true");
        }


        if ($(this)[0].hasAttribute("data-confirm")) {
            showDialog(false);
            Core.purgatory.push({
                trigger: 'ajaxRequest',
                data: {
                    formData: formData,
                    reload: reload,
                    url: url
                }
            });
        } else {
            ajaxRequest({
                formData: formData,
                reload: reload,
                url: url
            })
        }
    });

    $(document).on("click", ".main-search-button", function () {
        search();
    });


    $(document).on("change", ".urlTuner", function () {
        if(!$(this).hasClass('ignore-change')){
            var param = this.id;
            if(this.value == 'reset-value'){
                Core.urlObject.delete(param);
            }else{
                Core.urlObject.set(param, this.value);
                if (param === configuration.queryLimitParam)
                    Core.urlObject.set(configuration.pagingParam, 1);
            }
            window.location.href = Core.url;
        }
    });


    $(document).on("click", ".page-item", function () {
        if (!$(this).hasClass('pagnate')) {
            var pageNumber = Core.numberFormat(this.id);
            goToPageNumber(pageNumber);
        }
    });


    $(document).on("click", ".page-jump", function () {
        var pageNumber = 1;
        if (!$(this).hasClass('jump-to-start')) {
            pageNumber = pagingParameters.totalPages;
        }

        goToPageNumber(pageNumber);
    });

    $(document).on("click", ".pagnate", function () {
        var pageNumber = $(this).attr('data-direction') === 'fwd' ? currentPageNumber += 1 : currentPageNumber -= 1;
        goToPageNumber(pageNumber);
    });


    $(document).on("click", ".setSort", function (event) {
        if (!$(event.target).hasClass('delsort-i')) {
            var $this = event.target.nodeName === 'I' ? $(event.target).parent('th') : $(event.target);
            var target = $this.get(0).id;
            var value = $this.attr('data-sort') === 'DESC' || $this.attr('data-sort').length === 0 ? 'ASC' : 'DESC';
            var request = {
                action: 'set',
                override: false,
                target: target,
                value: value
            };
            Core.delayClicks++;
            if (Core.delayClicks === 1) {
                Core.delayTimer = setTimeout(function () {
                    setSort(request);
                    Core.delayClicks = 0;
                }, Core.delay);
            } else {
                clearTimeout(Core.delayTimer);
                Core.delayClicks = 0;
                request.override = true;
                request.value = $this.attr('data-sort');
                setSort(request);
            }
        } else {
            setSort({
                action: 'delete',
                target: $(event.target).parent('th').get(0).id
            });

        }
    }).on("dblclick", function (e) {
        e.preventDefault();
    });

    $(document).on("click", "#sortPriority span", function (event) {
        setSort({
            action: 'delete',
            target: $(this).text().trim()
        });
    });


    $(document).on("click", ".dateColumnPicker", function (event) {

        if (Core.urlObject.get(configuration.dateColumn) == null) {

            var formData = {
                formID: 'mainModalForm',
                data: {
                    dateColumn: ['Please select 1 option below', 'radio', '', false, [
                        {
                            comedate: 'val1',
                            comedate2: 'val2',
                        }
                    ]],


                }
            };
            Core.setModal({
                url: '#',
                title: 'Please choose a column to filter by',
                closeText: 'Cancel',
                actionText: 'Select',
                override: 'true',
                reload: false,
            });
            Core.buildForm(formData);
            Core.showModal('show');

        } else {
            $(this).addClass('rangepicker');
            $(this).removeClass('dateColumnPicker');
            $(this).trigger('click');
        }
    });
    $('.rangepicker').daterangepicker(rangeParams, rangeCB);
    rangeCB(RangeStart, RangeEnd);



    // if ($(".pagination-table").length) {
    //     var dragCheck = false;
    //     $(".pagination-table").each(function (key, value) {
    //         var id = this.id;
    //         $(".pagination-table").dragtable({
    //             dragaccept: '.draggable-col',
    //             excludeFooter: true,
    //             persistState: function (table) {
    //                 if (!window.sessionStorage) return;
    //                 var ss = window.sessionStorage;
    //                 id = table.el.closest('table').attr('id');
    //                 table.el.find('th').each(function (i) {
    //                     if (this.id != '') { table.sortOrder[this.id] = i; }
    //                 });
    //                 ss.setItem(id, JSON.stringify(table.sortOrder));
    //             },
    //             restoreState: eval('(' + window.sessionStorage.getItem(id) + ')')
    //         });
    //     });
    //
    // }

    function exportTable() {
        var fileName = $('input[name="exportFileName"]').val();
        var sheet_names = [];
        $('table').each(function (key, value){
            if (this.hasAttribute('data-name')) {
                sheet_names.push($(this).attr('data-name'));
            }
        });

        var readyState = false
        var excludeColumns = [];

        if ($('select[name="exportExcludeColumns"]').val() == undefined || $('select[name="exportExcludeColumns"]').val() == -1) {
            readyState = true;
        } else {
            if ($('select[name="exportExcludeColumns"]').val().length !== parseInt($('select[name="exportExcludeColumns"]').attr('size'))) {
                readyState = true;
                excludeColumns = $('select[name="exportExcludeColumns"]').val();
            }
        }
        $('.pagination-table th.action-column').each(function () {
            excludeColumns.push($(this).index());
        });

        if (Core.formIsFilled({
            target: 'mainModalForm',
            exclude: ['exportExcludeColumns'],
            highlight: true
        })) {
            if (readyState) {
                $('.shorten').each(function (key, value) {
                    var fullTxt = $(this).attr('data-full');
                    var excerpt = $(this).text();
                    $(this).text(fullTxt);
                    $(this).attr('data-full', excerpt)
                });
                $('.loader').fadeIn("slow", function () {
                    var type = $.inArray($('select[name="exportFileType"]').val(), Core.exportTypes) !== -1 ? $('select[name="exportFileType"]').val() : 'xlsx';
                    $('.pagination-table').tableExport({
                        fileName: fileName,
                        type: type,
                        mso: {
                            fileFormat: type,
                            worksheetName: sheet_names
                        },
                        ignoreColumn: excludeColumns
                    });
                    $('.shorten').each(function (key, value) {
                        var fullTxt = $(this).text()
                        var excerpt = $(this).attr('data-full');
                        $(this).text(excerpt);
                        $(this).attr('data-full', fullTxt)
                    });
                    $('.loader').fadeOut();
                    toastr.success('Export successful');
                });
            }
            Core.hideModal();
        }
    }

    $(document).on("click", "#export", function () {
        var modalTitle = `Export rows ${$('.row-start').text()} - ${$('.row-count').first().text()}`;
        var ExportName = $('title').text();
        if($('.m-0').length){
            ExportName = $('.m-0').first().text();
        }
        if (this.hasAttribute('data-title')) {
            ExportName = $(this).attr('data-title');
        }
        if (this.hasAttribute('data-modal-title')) {
            modalTitle = $(this).attr('data-modal-title');
        }
        Core.setModal({
            url: '#',
            title: modalTitle,
            type: 'exportTable',
            actionText: 'Export',
            reload: false,
            override: 'true',
        })

        var columns = ['Do not exclude'];
        var columnIndexes = [-1];
        $('.pagination-table th').each(function () {
            if (!$(this).hasClass('hidden')) {
                if ($(this).text().trim().length > 0) {
                    columns.push($(this).text());
                    columnIndexes.push($(this).index());
                }
            }
        });

        var params = {
            formID: 'mainModalForm',
            data: {
                exportFileName: ['File name', 'text', ExportName, false, []],
                exportFileType: ['File type', 'select', '', false, Core.exportTypes, Core.exportNiceNames],
                exportExcludeColumns : ['', 'hidden', '', false, columnIndexes, columns]
            }
        };

        //exclusion ability
        if (!this.hasAttribute('data-omit-exclusions')) {
            console.log(this.hasAttribute('data-omit-exclusions'))
            params.data.exportExcludeColumns = ['Exclude columns', 'multiSelect', '', false, columnIndexes, columns];
            params.data.html1 = ['<small class="text-muted">Hold down the Ctrl (windows) or Command (Mac) button to select multiple options.</small>', 'html'];
        }

        Core.buildForm(params);
        Core.showModal('show');

    });

    function generateMinifiedStyles(){

        $('head').append(`<style id="mobile-styles"></style>`);
        $('#mobile-styles').append('@media screen and (max-width:992px) {');
        $('table th').each(function(){
            var title = $(this).text().trim();
            $('#mobile-styles').append(`.pagination-table tbody tr td:nth-child(${$(this).index()+1}):before {content: "${title}"}`);
        });
        $('#mobile-styles').append('}');
    }

    $(document).on("click", ".action-trigger", function () {
        switch ($(this).attr('data-type')) {
            case 'exportTable':
                exportTable();
                break;
        }
    });

    initPaging(false);
    generateMinifiedStyles();
    $('#export').text(`Export rows ${$('.row-start').text()} - ${$('.row-count').first().text()}`)

});





