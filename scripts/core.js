class BaseClass {
    url;
    uri;
    urlObject;
    currentPage;
    delay = 400;
    purgatory = [];
    delayClicks = 0;
    delayTimer = null;
    account = null;
    pendingActions = [];
    verificationMode = !1;
    verifyElements = {};
    exceptionMode = !1;
    exportTypes = ["xlsx", "pdf", "csv", "txt", "json"];
    exportNiceNames = ["Excel", "PDF", "CSV", "TXT", "JSON"];
    defaultExportName = document.title;
    constructor(t) {
        this.postDir = t, this.baseURL = window.location.href, this.url = new URL(window.location.href), this.urlObject = this.url.searchParams, this.uri = window.location.href.split(/[\/\?]+/), this.account = this.urlObject.get("account")
    }
    static throwCriticalError(t) {
        toastr.error("An error occurred"), $(document).Toasts("create", {
            position: "bottomRight",
            delay: 1e4,
            autoHide: !0,
            title: '<span class="text-danger"><i class="fas fa-times-circle"></i></span> An error occurred',
            body: "<p>" + t + "</p>"
        })
    }
    setNiceError(t) {
        $("#niceError").length && $("#niceError").text(t.message)
    }
    handleAjaxResponse(t) {
        if ((t.data.hasOwnProperty("popupMsg") && (t.data.result ? toastr.success(t.data.popupMsg) : toastr.error(t.data.popupMsg)), t.data.hasOwnProperty("redirect") && t.data.hasOwnProperty("view")) ? (null != t.element ? t.element.find("#template_inject_container").html(t.data.view) : $("#template_inject_container").html(t.data.view), setTimeout(function() {
            window.location.href = t.data.redirect
        }, 1e3)) : t.data.hasOwnProperty("view") ? null != t.element ? t.element.find("#template_inject_container").html(t.data.view) : $("#template_inject_container").html(t.data.view) : t.data.hasOwnProperty("redirect") ? setTimeout(function() {
            window.location.href = t.data.redirect
        }, t.data.hasOwnProperty("timeout") ? t.data.timeout : 1e3) : t.data.hasOwnProperty("divReload") ? t.data.divReload.length > 1 && Core.reLoader({
            injectTarget: t.data.divReload,
            sourceTarget: t.data.divReload
        }) : t.data.hasOwnProperty("reload") && t.data.reload && setTimeout(function() {
            window.location.reload()
        }, 1e3), t.data.hasOwnProperty("response") && this.setNiceError({
            message: t.data.response
        }), t.data.hasOwnProperty("errors")) {
            var e = t.data.errors;
            $.isArray(e) && $.each(e, function(t, e) {
                $(document).Toasts("create", {
                    position: "bottomRight",
                    delay: 5e3,
                    autoHide: !1,
                    title: '<span class="text-danger"><i class="fas fa-times-circle"></i></span> An error occurred',
                    body: "<p>" + e + "</p>"
                })
            })
        }
    }
    ajaxRequest(parameters) {
        var postUrl = this.postDir;
        parameters.hasOwnProperty("method") && void 0 != parameters.method ? postUrl += parameters.method : parameters.hasOwnProperty("url") && void 0 != parameters.url && ("/" == parameters.url[0] ? postUrl = parameters.url : postUrl += parameters.url), parameters.hasOwnProperty("controller") && void 0 != parameters.controller && (parameters.data.controller = parameters.controller);
        let response = !1,
            aJaxParams = {
                type: "POST",
                url: postUrl,
                async: !1,
                method: "POST",
                data: parameters.data,
                success: function(t) {
                    $(".loader,.nested-loader").fadeOut();
                    try {
                        response = parameters.strict ? $.parseJSON(t) : t
                    } catch (e) {
                        t.trim().length > 0 && BaseClass.throwCriticalError(t)
                    }
                },
                error: function(xhr, status, error) {
                    var err = eval("(" + xhr.responseText + ")");
                    BaseClass.throwCriticalError(err)
                }
            };
        parameters.hasOwnProperty("uploadSupport") && parameters.uploadSupport && (aJaxParams.cache = !1, aJaxParams.contentType = !1, aJaxParams.processData = !1), $.ajax(aJaxParams);
        var element = null;
        return parameters.hasOwnProperty("element") ? element = parameters.element : response.hasOwnProperty("element") && (element = response.element), !1 != response && this.handleAjaxResponse({
            element: element,
            data: response
        }), response
    }
    logout() {
        this.ajaxRequest({
            url: "Logout",
            strict: !1,
            data: {
                controller: "Authentication",
                logout: !0
            }
        }), window.location.href = "/account/login"
    }
    loadRawHtml(t, e = !1) {
        var r = {
            url: "getRawHtmlTemplate",
            strict: !1,
            data: {
                path: t
            }
        };
        return !1 != e && (r.controller = e), Core.ajaxRequest(r)
    }
    getTwigTemplate(t, e) {
        return Core.ajaxRequest({
            url: "getTwigTemplate",
            strict: !1,
            controller: "admin",
            data: {
                template: t,
                params: e
            }
        })
    }
    formIsFilled(t) {
        var e = !0;
        return $(".write-error").remove(), $.each($("#" + t.target + " .form-control"), function(r) {
            !$(this).hasClass("not-required") && -1 === $.inArray($(this).attr("name"), t.exclude) && (0 === this.value.length && "hidden" !== $(this).attr("type") ? (e = !1, t.highlight && ($(this).css("border", "1px solid red"), $(this).after('<p class="write-error"><small class="text-danger">Required <i class="fa fa-level-up" aria-hidden="true"></i></small></p>'))) : $(this).css("border", "1px solid #ddd"))
        }), $.each($(".required-radio-options"), function() {
            "false" === $(this).attr("data-selected") && (e = !1)
        }), e
    }
    formatBytes(t, e = 2) {
        if (0 === t) return "0 Bytes";
        let r = 1024,
            a = e < 0 ? 0 : e,
            o = ["Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"],
            s = Math.floor(Math.log(t) / Math.log(r));
        return parseFloat((t / Math.pow(r, s)).toFixed(a)) + " " + o[s]
    }
    generateKey(t) {
        for (var e = "", r = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", a = r.length, o = 0; o < t; o++) e += r.charAt(Math.floor(Math.random() * a));
        return e
    }
    numberFormat(t) {
        return parseInt(t.replace(/[^\d]/g, ""))
    }
    replaceUnderscores(t) {
        return t.replace(/_/g, " ")
    }
    postRequest(t) {
        let e = {},
            r = $(t),
            a = r.text(),
            o = r.attr("data-values").split(","),
            s = r.attr("data-url"),
            l = 0;
        $.each(r.attr("data-keys").split(","), function(t, r) {
            e[r] = o[l++]
        });
        let i = this.ajaxRequest({
                strict: !0,
                url: s,
                data: e
            }),
            n = !0;
        i.hasOwnProperty("result") && !i.result && (n = !1), n && (r.text(r.attr("data-temp-txt")), setTimeout(function() {
            r.text(a)
        }, 2e3))
    }
    aJaxFormSubmit(t) {
        let e = $(t);
        var r = {
                data: e.serialize()
            },
            a = e.attr("action"),
            o = e.attr("data-method");
        void 0 !== a && !1 !== a ? r.url = a : void 0 !== o && !1 !== o && (r.method = o);
        try {
            let s = $.parseJSON(this.ajaxRequest(r));
            this.handleAjaxResponse({
                element: e,
                data: s
            })
        } catch (l) {
            BaseClass.throwCriticalError("Failed to parse JSON response. data returned in incorrect format or page not found.")
        }
    }
    reLoader(t) {
        $.ajax({
            url: window.location.href,
            type: "GET",
            success: function(e) {
                $(t.injectTarget).html($(e).find(t.sourceTarget).html())
            }
        })
    }
}