/*!

 @Title: PicSign
 @Description：jQuery图片标注组件
 @Site: www.artlessbruin.cn
 @gitee：https://gitee.com/ArtlessBruin/PicSign
 @Author: ArtlessBruin
 @License：MIT
 @Version：1.0.0

 */

(function ($) {
    //随机数 用于生成唯一标识
    function untrue_guid() {
        function S4() {
            return ((1 + Math.random()) * 0x10000 | 0).toString(16).substring(1);
        }
        return S4() + S4() + S4() + S4();
    }
    //webuiPopover默认设置
    var webuiPopoverSetting = {
        title: "",
        content: "",
        trigger: 'hover',//click
        multi: true
    };
    //picsign 默认设置
    var picsigndefaults = {
        picurl: null,
        signdata: [],
        editable: {//是否可编辑
            add: true,//是否可添加
            update: true,//是否可修改
            del: true,//是否可删除
            move: true//是否可移动
        },
        signclass: 'signdot',
        popwidth: 400,
        popheight: 247,
        inputwidth: 400,
        inputheight: 247,
        beforeadd: function (data) {
        },
        onadd: function (data) {
        },
        beforeupdate: function (data) {
        },
        onupdate: function (data) {
        },
        beforedel: function (data) {
        },
        ondel: function (data) {
        }
    };
    //开放调用的方法
    var allowedMethods = [
        'getData',//获取数据
        'addSign',//添加数据
        'toggle',//切换显示
        'destroy',//销毁
        'hide',//隐藏
        'show',//显示
    ];
    //jQuery扩展
    $.fn.picsign = function (option) {
        var value;
        var args = Array.prototype.slice.call(arguments, 1);
        var options = $.extend({}, picsigndefaults, typeof option === 'object' && option);
        this.each(function () {
            var $this = $(this);
            var data = $this.data('jquery.picsign');
            if (typeof option === 'string') {
                if ($.inArray(option, allowedMethods) < 0) {
                    throw new Error("Unknown method: " + option);
                }
                if (!data) {
                    return;
                }
                value = data[option].apply(data, args);
                if (option === 'destroy') {
                    $this.removeData('jquery.picsign');
                }
            }
            if (!data) {
                $this.data('jquery.picsign', data = new PicSign(this, options));
                value = true;
            }
        });
        return value;
        //return typeof value === 'undefined' ? this : value;
    };
    //PicSign
    class PicSign {
        //构造函数
        constructor(el, options) {
            this.ID = untrue_guid();
            this.Options = options;
            this.$_el = $(el);
            this.$el = null;
            this.Data = [];
            this.signMoveId = null;
            this.Interval = null;
            this.init();
        }
        //初始化
        init() {
            this.dominit();
            this.datainit();
        }
        //销毁
        destroy() {
            window.clearInterval(this.Interval);
            this.domget().webuiPopover('destroy');
            this.$el.remove();
            return true;
        }
        //DOM初始化
        dominit() {
            var $this = this;
            $this.$_el.html("");
            var img = $('<img class="picsignimg" src="' + $this.Options.picurl + '" />');
            var div = $("<div class='picsign'></div>");
            $this.$_el.append(div);
            $this.$_el.append(img);
            $this.$el = $(div);
            $this.Interval = setInterval(function () {
                $this.$el.css('margin-left', $(img).position().left - $this.$el.position().left);
                $this.$el.css('margin-top', $(img).position().top - $this.$el.position().top);
                $this.$el.width($(img).width());
                $this.$el.height($(img).height());
            }, 20);
            var get_Btn_signid = function (btn) {
                if (btn) {
                    var signid = $(btn).data("signid");
                    return signid;
                }
                return null;
            };
            //获取位置
            var getlocation = function (e) {
                var signleft = e.clientX - ($this.$el.offset().left - $(document).scrollLeft());
                var signtop = e.clientY - ($this.$el.offset().top - $(document).scrollTop());
                var left = (signleft / $this.$el.width() * 100).toFixed(4) + "%";
                var top = (signtop / $this.$el.height() * 100).toFixed(4) + "%";
                return { left: left, top: top };
            };
            var prompt = function (success, msg) {
                layer.prompt({ title: '输入标注', formType: 2, width: $this.Options.inputwidth, height: $this.Options.inputheight, value: msg }, function (text, index) {
                    layer.close(index);
                    if (text.length > 0) {
                        if (success && typeof success === "function") {
                            success(text);
                        }
                    }
                });
            };
            //双击
            $this.$el.on("dblclick", function (e) {
                var location = getlocation(e);
                if ($this.Options.editable !== false && ($this.Options.editable === true || $this.Options.editable.add !== false)) {
                    prompt(function (text) {
                        $this.dataset({
                            left: location.left,
                            top: location.top,
                            msg: text
                        });
                    });
                }
                $this.movecancel();
            });
            //单机
            $this.$el.on("click", function (e) {
                var location = getlocation(e);
                if ($this.signMoveId) {
                    //移动
                    $this.dataset({
                        signid: $this.signMoveId,
                        left: location.left,
                        top: location.top
                    });
                }
                $this.movecancel();
            });
            //删除
            $(document).on('click', '.picsign_bdel_' + $this.ID, function () {
                var signid = get_Btn_signid($(this));
                if (signid) {
                    WebuiPopovers.hideAll();
                    $this.datadel(signid);
                }
            });
            //移动
            $(document).on('click', '.picsign_bmove_' + $this.ID, function () {
                var signid = get_Btn_signid($(this));
                if (signid) {
                    WebuiPopovers.hideAll();
                    $this.signMoveId = signid;
                    $this.$el.css("cursor", "move");
                }
            });
            //编辑
            $(document).on('click', '.picsign_bedit_' + $this.ID, function () {
                var signid = get_Btn_signid($(this));
                if (signid) {
                    WebuiPopovers.hideAll();
                    var signdata = $this.dataget(signid);
                    prompt(function (text) {
                        $this.dataset({
                            signid: signdata.signid,
                            msg: text
                        });
                    }, signdata.msg);
                }
            });
        }
        //DOM添加
        domadd(signdata) {
            var $this = this;
            if (signdata) {
                var sign = $("<div class='" + $this.Options.signclass + "' data-signid='" + signdata.signid + "'></div>");
                $this.$el.append(sign);
                sign.css({
                    "left": signdata.left,
                    "top": signdata.top,
                    "background-color": signdata.background_color,
                    "background-image": signdata.background_image,
                    "display": "block"
                });
                //同步显示
                $this.domget().hide();
                setTimeout(function () {
                    $this.domget().show();
                }, 10);
                //标记点击事件
                if('signClickFunction' in $this.Options){
                    sign.on('click',{'name':signdata.title},$this.Options.signClickFunction);
                }
                $this.dompopover(signdata, true);
            }
        }
        //设置和更新popover
        dompopover(signdata, isadd) {
            var $this = this;
            if (signdata) {
                var sign = $this.domget(signdata.signid);
                if (sign) {
                    if (!isadd) {
                        WebuiPopovers.updateContent(sign, signdata.msg);
                    }
                    else {
                        var updatebtn = $("<a class='btn btn-xs btn-info' style='margin-right:5px'>编辑</a>");
                        var movebtn = $("<a class='btn btn-xs btn-warning' style='margin-right:5px'>移动</a>");
                        var delbtn = $("<a class='btn btn-xs btn-danger' style='margin-right:5px'>删除</a>");
                        var signbtn = $("<div class='pull-right'></div>"), del = true, update = true, move = true;
                        if ($this.Options.editable !== false) {
                            if ($this.Options.editable !== true) {
                                if ($this.Options.editable.update === false) {
                                    update = false;
                                }
                                if ($this.Options.editable.move === false) {
                                    move = false;
                                }
                                if ($this.Options.editable.del === false) {
                                    del = false;
                                }
                            }
                            if (update) {
                                signbtn.append(updatebtn);
                                updatebtn.attr("data-signid", signdata.signid);
                                updatebtn.addClass("picsign_bedit_" + $this.ID);
                            }
                            if (move) {
                                signbtn.append(movebtn);
                                movebtn.attr("data-signid", signdata.signid);
                                movebtn.addClass("picsign_bmove_" + $this.ID);
                            }
                            if (del) {
                                signbtn.append(delbtn);
                                delbtn.attr("data-signid", signdata.signid);
                                delbtn.addClass("picsign_bdel_" + $this.ID);
                            }
                        }
                        sign.webuiPopover($.extend({}, webuiPopoverSetting, {
                            title: signdata.title + signbtn.prop("outerHTML"),
                            width: $this.Options.popwidth,
                            height: $this.Options.popheight,
                            content: signdata.msg,
                            onShow: function () {
                                $this.movecancel();
                            }
                        }));
                    }

                }
            }
        }
        //DOM更新
        domupdate(signdata) {
            if (signdata.signid) {
                this.domget(signdata.signid).css({
                    "left": signdata.left,
                    "top": signdata.top,
                    "display": "block"
                });
                this.dompopover(signdata, false);
            }
        }
        //DOM删除
        domdel(signid) {
            this.domget(signid).webuiPopover('destroy');
            this.domget(signid).remove();
        }
        //DOM获取
        domget(signid) {
            if (signid) {
                return $("." + this.Options.signclass + "[data-signid='" + signid + "']");
            }
            else {
                return $("." + this.Options.signclass);
            }
        }
        //取消移动
        movecancel() {
            this.signMoveId = null;
            this.$el.css("cursor", "");
        }
        //数据初始化
        datainit() {
            this.dataset(this.Options.signdata, false);
        }
        //数据设置(单个或数组)
        dataset(signdata, istrigger) {
            var $this = this;
            if (signdata) {
                if (Array.isArray(signdata)) {
                    for (var i = 0; i < signdata.length; i++) {
                        $this.dataset_one(signdata[i], istrigger);
                    }
                }
                else {
                    $this.dataset_one(signdata, istrigger);
                }
            }
        }
        //数据设置(单个)
        dataset_one(signdata, istrigger) {
            var $this = this;
            if (signdata) {
                if (signdata.signid) {
                    //修改
                    var data = $this.dataget(signdata.signid);
                    var copy = { left: data.left, top: data.top, msg: data.msg };
                    if (data) {
                        if (signdata.left) {
                            data.left = signdata.left;
                        }
                        if (signdata.top) {
                            data.top = signdata.top;
                        }
                        if (signdata.msg) {
                            data.msg = signdata.msg;
                        }
                        if ($this.event("beforeupdate", data, istrigger)) {
                            $this.domupdate(data);
                            $this.event("onupdate", data, istrigger);
                        }
                        else {
                            data.left = copy.left;
                            data.top = copy.top;
                            data.msg = copy.msg;
                        }
                    }
                }
                else {
                    //添加
                    if (signdata.left && signdata.top && signdata.msg) {
                        var indexId = $this.Data.length;
                        signdata['signid'] = untrue_guid() + "_" + indexId;
                        if ($this.event("beforeadd", signdata, istrigger)) {
                            $this.Data.push(signdata);
                            $this.domadd(signdata);
                            $this.event("onadd", signdata, istrigger);
                        }
                    }
                }
            }
        }
        //数据获取
        dataget(signid) {
            var $this = this;
            for (var i = 0; i < $this.Data.length; i++) {
                if ($this.Data[i].signid === signid) {
                    return $this.Data[i];
                }
            }
            return null;
        }
        //数据删除
        datadel(signid, istrigger) {
            var $this = this;
            for (var i = 0; i < $this.Data.length; i++) {
                if ($this.Data[i].signid === signid) {
                    layer.confirm('是否删除标注？', { icon: 0, title: '删除标注' }, function (index) {
                        layer.close(index);
                        if ($this.event("beforedel", $this.Data[i], istrigger)) {
                            $this.domdel(signid);
                            $this.event("ondel", $this.Data[i], istrigger);
                            $this.Data.splice(i, 1);
                        }
                    });
                    break;
                }
                else {
                    continue;
                }
            }
        }
        //事件
        event(e, signdata, istrigger) {
            var $this = this;
            if (istrigger !== false) {
                var fn;
                switch (e) {
                    case "beforeadd":
                        fn = $this.Options.beforeadd;
                        break;
                    case "onadd":
                        fn = $this.Options.onadd;
                        break;
                    case "beforeupdate":
                        fn = $this.Options.beforeupdate;
                        break;
                    case "onupdate":
                        fn = $this.Options.onupdate;
                        break;
                    case "beforedel":
                        fn = $this.Options.beforedel;
                        break;
                    case "ondel":
                        fn = $this.Options.ondel;
                        break;
                    default: fn = null;
                }
                if (fn && typeof fn === 'function') {
                    if (fn(signdata) === false) {
                        return false;
                    }
                }
            }
            return true;
        }
        //获取标记数据
        getData() {
            return this.Data;
        }
        //隐藏
        hide(signid) {
            this.domget(signid).hide();
        }
        //隐藏
        show(signid) {
            this.domget(signid).show();
        }
        //添加标注
        addSign(signdata, istrigger) {
            if (signdata) {
                this.dataset(signdata, istrigger);
            }
        }
        //切换显示状态
        toggle() {
            this.domget().toggle();
        }
    }
})(jQuery);