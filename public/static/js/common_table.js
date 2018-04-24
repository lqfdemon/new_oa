
    var TableInit = function (tableId,dataUrl,listIdName,columnList,onClickFunction,searchParams,export_flag) {
        var oTableInit = new Object();
        console.log(export_flag);
        //初始化Table
        oTableInit.Init = function () {
            $('#'+tableId).bootstrapTable({
                url: dataUrl,         //请求后台的URL（*）
                method: 'get',                      //请求方式（*）
                striped: false,                      //是否显示行间隔色
                pageSize: 20,                       //每页的记录行数（*）
                pageList: [10,20, 30,40],        //可供选择的每页的行数（*）       //是否使用缓存，默认为true，所以一般情况下需要设置一下这个属性（*）
                pagination: true,                   //是否显示分页（*）
                uniqueId: listIdName,
                columns: columnList,
                onClickRow:onClickFunction,
                queryParams:searchParams,
                showExport: export_flag,//显示导出按钮  
                exportDataType: "basic",//导出类型  
            });
        };
        return oTableInit;
    };
