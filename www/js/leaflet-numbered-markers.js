L.NumberedDivIcon = L.Icon.extend({
	options: {
   		iconUrl: 'img/marker-square.png',
    	number: '',
    	shadowUrl: null,
    	iconSize: new L.Point(25, 25),
		iconAnchor: new L.Point(13, 13),
		popupAnchor: new L.Point(0, -20),
		className: 'leaflet-div-icon'
	},
 
	createIcon: function () {
		var div = document.createElement('div');
		var img = this._createImg(this.options['iconUrl']);
		var numdiv = document.createElement('div');
		numdiv.setAttribute ( "class", "number" );
		numdiv.setAttribute ( "data-number", this.options['number']);
		numdiv.innerHTML = this.options['number'] || '?';
		div.appendChild ( img );
		div.appendChild ( numdiv );
		this._setIconStyles(div, 'icon');
		return div;
	}
 
});