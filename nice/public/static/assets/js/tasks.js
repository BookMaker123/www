!function (t) {
    var e = {};



    function a(s) {
        if (e[s])
            return e[s].exports;
        var n = e[s] = {
            i: s,
            l: !1,
            exports: {}
        };
        return t[s].call(n.exports, n, n.exports, a),
            n.l = !0,
            n.exports
    }
    a.m = t,
        a.c = e,
        a.d = function (t, e, s) {
            a.o(t, e) || Object.defineProperty(t, e, {
                enumerable: !0,
                get: s
            })
        }
        ,
        a.r = function (t) {
            "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(t, Symbol.toStringTag, {
                value: "Module"
            }),
                Object.defineProperty(t, "__esModule", {
                    value: !0
                })
        }
        ,
        a.t = function (t, e) {
            if (1 & e && (t = a(t)),
                8 & e)
                return t;
            if (4 & e && "object" == typeof t && t && t.__esModule)
                return t;
            var s = Object.create(null);
            if (a.r(s),
                Object.defineProperty(s, "default", {
                    enumerable: !0,
                    value: t
                }),
                2 & e && "string" != typeof t)
                for (var n in t)
                    a.d(s, n, function (e) {
                        return t[e]
                    }
                        .bind(null, n));
            return s
        }
        ,
        a.n = function (t) {
            var e = t && t.__esModule ? function () {
                return t.default
            }
                : function () {
                    return t
                }
                ;
            return a.d(e, "a", e),
                e
        }
        ,
        a.o = function (t, e) {
            return Object.prototype.hasOwnProperty.call(t, e)
        }
        ,
        a.p = "",
        a(a.s = 26)
}({
    26: function (t, e, a) {
        t.exports = a(27)
    },
    27: function (t, e) {
        function a(t, e) {
            for (var a = 0; a < e.length; a++) {
                var s = e[a];
                s.enumerable = s.enumerable || !1,
                    s.configurable = !0,
                    "value" in s && (s.writable = !0),
                    Object.defineProperty(t, s.key, s)
            }
        }
        var s, n, r, o, d, l, c, i, u, k, f, p = function () {
            function t() {
                !function (t, e) {
                    if (!(t instanceof e))
                        throw new TypeError("Cannot call a class as a function")
                }(this, t)
            }
            var e, p, b;
            return e = t,
                b = [{
                    key: "initTasks",
                    value: function () {
                        var t, e, a, p, b = this;
                        n = jQuery(".js-tasks"),
                            r = jQuery("#js-task-form"),
                            o = jQuery("#js-task-input"),
                            l = jQuery(".js-task-list"),
                            c = jQuery(".js-task-list-starred"),
                            i = jQuery(".js-task-list-completed"),
                            u = jQuery(".js-task-badge"),
                            k = jQuery(".js-task-badge-starred"),
                            f = jQuery(".js-task-badge-completed"),
                            s = 10,
                            this.badgesUpdate(),
                            r.on("submit", function (t) {
                                t.preventDefault(),
                                    (d = o.prop("value")) && (b.taskAdd(d),
                                        o.prop("value", "").focus())
                            }),
                            n.on("click", ".js-task-status", function (a) {
                                a.preventDefault(),
                                    t = jQuery(a.currentTarget).closest(".js-task"),
                                    e = t.data("task-id"),
                                    t.data("task-completed") ? b.taskSetActive(e) : b.taskSetCompleted(e)
                            }),
                            n.on("click", ".js-task-star", function (t) {
                                a = jQuery(t.currentTarget).closest(".js-task"),
                                    p = a.data("task-id"),
                                    a.data("task-starred") ? b.taskStarRemove(p) : b.taskStarAdd(p)
                            }),
                            n.on("click", ".js-task-remove", function (t) {
                                a = jQuery(t.currentTarget).closest(".js-task"),
                                    p = a.data("task-id"),
                                    b.taskRemove(p)
                            })
                    }
                }, {
                    key: "badgesUpdate",
                    value: function () {
                        //统计量
                        u.text(l.children().length  || ""),
                            k.text(c.children().length || ""),
                            f.text(i.children().length || "")
                    }
                }, {
                    key: "taskAdd",
                    value: function (t) {
                        //添加
                        var form_data = "t=" + t + "&s=taskAdd" + "&c=";
                        $.post(aiguovip.tasks_url, form_data, function (res) {
                            if (res.code) {
                                AAiguovip.notify(res.msg + res.id, 'success');
                                l.prepend('\n            <div class="js-task block block-rounded block-fx-pop block-fx-pop mb-2 animated fadeIn" data-task-id="'.concat(res.id, '" data-task-completed="false" data-task-starred="false">\n                <table class="table table-borderless table-vcenter mb-0">\n                    <tr>\n                        <td class="text-center pr-0" style="width: 38px;">\n                            <div class="js-task-status custom-control custom-checkbox custom-checkbox-rounded-circle custom-control-primary custom-control-lg">\n                                <input type="checkbox" class="custom-control-input" id="tasks-cb-id').concat(res.id, '" name="tasks-cb-id').concat(res.id, '">\n                                <label class="custom-control-label" for="tasks-cb-id').concat(res.id, '"></label>\n                            </div>\n                        </td>\n                        <td class="js-task-content font-w600 pl-0">\n                            ').concat(jQuery("<span />").text(t).html(), '\n                        </td>\n                        <td class="text-right" style="width: 100px;">\n                            <button type="button" class="js-task-star btn btn-sm btn-link text-warning">\n                                <i class="far fa-star fa-fw"></i>\n                            </button>\n                            <button type="button" class="js-task-remove btn btn-sm btn-link text-danger">\n                                <i class="fa fa-times fa-fw"></i>\n                            </button>\n                        </td>\n                    </tr>\n                </table>\n            </div>\n        '));
                                this.badgesUpdate();
                            } else {
                                AAiguovip.notify('任务添加失败', 'danger');
                            }
                        }).fail(function () {
                            AAiguovip.notify('连接服务器错误！', 'danger');
                        });
                        this.badgesUpdate()
                    }
                }, {
                    key: "taskRemove",
                    value: function (t) {
                        var form_data = "t=" + t + "&s=taskRemove" + "&c=";
                        $.post(aiguovip.tasks_url, form_data, function (res) {
                            if (res.code) {
                                AAiguovip.notify('删除' + t, 'success');
                                jQuery('.js-task[data-task-id="' + t + '"]').remove();
                            } else {
                                AAiguovip.notify('删除失败', 'danger');
                            }
                        }).fail(function () {
                            AAiguovip.notify('连接服务器错误！', 'danger');
                        });
                        this.badgesUpdate()
                    }
                }, {
                    key: "taskStarAdd",
                    value: function (t) {
                        var form_data = "t=" + t + "&s=taskStarAdd" + "&c=";
                        $.post(aiguovip.tasks_url, form_data, function (res) {
                            if (res.code) {
                                AAiguovip.notify('任务收藏成功：' + t, 'success');
                                var e = jQuery('.js-task[data-task-id="' + t + '"]');
                                e.length > 0 && (e.data("task-starred", !0),
                                    e.find(".js-task-star > i").toggleClass("fa far"), e.data("task-completed") || e.prependTo(c))
                            } else {
                                AAiguovip.notify('任务收藏失败', 'danger');
                            }
                        }).fail(function () {
                            AAiguovip.notify('连接服务器错误！', 'danger');
                        });
                        this.badgesUpdate()
                    }
                }, {
                    key: "taskStarRemove",
                    value: function (t) {
                        var form_data = "t=" + t + "&s=taskStarRemove" + "&c=";
                        $.post(aiguovip.tasks_url, form_data, function (res) {
                            if (res.code) {
                                AAiguovip.notify('任务取消收藏' + t, 'success');
                                var e = jQuery('.js-task[data-task-id="' + t + '"]');
                                e.length > 0 && (e.data("task-starred", !1),
                                    e.find(".js-task-star > i").toggleClass("fa far"),
                                    e.data("task-completed") || e.prependTo(l));
                            } else {
                                AAiguovip.notify('取消收藏失败', 'danger');
                            }
                        }).fail(function () {
                            AAiguovip.notify('连接服务器错误！', 'danger');
                        });
                        this.badgesUpdate()

                    }
                }, {
                    key: "taskSetActive",
                    value: function (t) {
                        var form_data = "t=" + t + "&s=taskSetActive" + "&c=";
                        $.post(aiguovip.tasks_url, form_data, function (res) {
                            if (res.code) {
                                //设置未完成

                                var e = jQuery('.js-task[data-task-id="' + t + '"]');
                                e.length > 0 && (e.data("task-completed", !1),
                                    e.find(".table").toggleClass("bg-body"),
                                    e.find(".js-task-status > input").prop("checked", !1),
                                    e.find(".js-task-content > del").contents().unwrap(),
                                    e.data("task-starred") ? e.prependTo(c) : e.prependTo(l))
                            } else {
                                AAiguovip.notify('操作失败', 'danger');
                            }
                        }).fail(function () {
                            AAiguovip.notify('连接服务器错误！', 'danger');
                        });
                        this.badgesUpdate()          
                    }
                }, {

                    key: "taskSetCompleted",
                    value: function (t) {
                  
                        var form_data = "t=" + t + "&s=taskSetCompleted" + "&c=";
                        $.post(aiguovip.tasks_url, form_data, function (res) {
                            if (res.code) {
                                //设置完成
                                var e = jQuery('.js-task[data-task-id="' + t + '"]');
                                e.length > 0 && (e.data("task-completed", !0),
                                    e.find(".table").toggleClass("bg-body"),
                                    e.find(".js-task-status > input").prop("checked", !0),
                                    e.find(".js-task-content").wrapInner("<del></del>"),
                                    e.prependTo(i))
                            } else {
                                AAiguovip.notify('操作失败', 'danger');
                            }
                        }).fail(function () {
                            AAiguovip.notify('连接服务器错误！', 'danger');
                        });
                       this.badgesUpdate();

                        
                    }
                }, {
                    key: "init",
                    value: function () {
                        this.initTasks()
                    }
                }],
                (p = null) && a(e.prototype, p),
                b && a(e, b),
                t
        }();
        jQuery(function () {
            p.init()
        })
    }
});
