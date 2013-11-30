/*
 * draggable_methods.js
 */
(function($) {

function shouldmove(why) {
	drag(el, 50, 50);
	moved(50, 50, why);
}

function shouldnotmove(why) {
	drag(el, 50, 50);
	moved(0, 0, why);
}

module("draggable: methods");

test("init", function() {
	expect(6);

	$("<div></div>").appendTo('body').draggable().remove();
	ok(true, '.draggable() called on element');

	$([]).draggable();
	ok(true, '.draggable() called on empty collection');

	$("<div></div>").draggable();
	ok(true, '.draggable() called on disconnected DOMElement');

	$("<div></div>").draggable().draggable("foo");
	ok(true, 'arbitrary method called after init');

	$("<div></div>").draggable().data("foo.draggable");
	ok(true, 'arbitrary option getter after init');

	$("<div></div>").draggable().data("foo.draggable", "bar");
	ok(true, 'arbitrary option setter after init');
});

test("destroy", function() {
	expect(6);

	$("<div></div>").appendTo('body').draggable().draggable("destroy").remove();
	ok(true, '.draggable("destroy") called on element');

	$([]).draggable().draggable("destroy");
	ok(true, '.draggable("destroy") called on empty collection');

	$("<div></div>").draggable().draggable("destroy");
	ok(true, '.draggable("destroy") called on disconnected DOMElement');

	$("<div></div>").draggable().draggable("destroy").draggable("foo");
	ok(true, 'arbitrary method called after destroy');

	$("<div></div>").draggable().draggable("destroy").data("foo.draggable");
	ok(true, 'arbitrary option getter after destroy');

	$("<div></div>").draggable().draggable("destroy").data("foo.draggable", "bar");
	ok(true, 'arbitrary option setter after destroy');
});

test("enable", function() {
	expect(6);
	el = $("#draggable2").draggable({ disabled: true });
	shouldnotmove('.draggable({ disabled: true })');
	el.draggable("enable");
	shouldmove('.draggable("enable")');
	equals(el.data("disabled.draggable"), false, "disabled.draggable getter");

	el.draggable("destroy");
	el.draggable({ disabled: true });
	shouldnotmove('.draggable({ disabled: true })');
	el.data("disabled.draggable", false);
	equals(el.data("disabled.draggable"), false, "disabled.draggable setter");
	shouldmove('.data("disabled.draggable", false)');
});

test("disable", function() {
	expect(6);
	el = $("#draggable2").draggable({ disabled: false });
	shouldmove('.draggable({ disabled: false })');
	el.draggable("disable");
	shouldnotmove('.draggable("disable")');
	equals(el.data("disabled.draggable"), true, "disabled.draggable getter");

	el.draggable("destroy");

	el.draggable({ disabled: false });
	shouldmove('.draggable({ disabled: false })');
	el.data("disabled.draggable", true);
	equals(el.data("disabled.draggable"), true, "disabled.draggable setter");
	shouldnotmove('.data("disabled.draggable", true)');
});

})(jQuery);
