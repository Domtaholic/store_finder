/**
 * This file is developed by evoweb.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

declare module omnivore {
  function kml(url: string): any;
}

declare interface MapConfiguration {
  active: Boolean,
  afterSearch: number;
  center?: {
    lat: number,
    lng: number
  };
  zoom?: number;

  apiConsoleKey: string,
  apiUrl: string,
  language: string,

  markerIcon: string,
  apiV3Layers: string,
  kmlUrl: string,
  mapStyles?: Array<object>,

  renderSingleViewCallback(location: object, template: string, marker: any): void,
  handleCloseButtonCallback(event: Event, button: object): void,
}

declare interface BackendConfiguration {
  uid: string,
  latitude: number,
  longitude: number,
  zoom: number
}

declare interface Location {
  name: string,
  hidden: number,
  lat: number,
  lng: number,
  information: {
    index: number,
    uid: number,
    lat: number,
    lng: number,
    name: string,
    storeid: string,
    address: string,
    additionaladdress: string,
    city: string,
    person: string,
    zipcode: string,
    products: string,
    email: string,
    phone: string,
    mobile: string,
    fax: string,
    hours: string,
    url: string,
    notes: string,
    state: string,
    country: string,
    icon: string,
    media: string,
    image: Array<string>,
    layer: string,
    layerCoords: Array<google.maps.LatLng>,
    attributes: Array<number>,
  },
  marker: any
}

declare interface Window {
  mapConfiguration: MapConfiguration,
  locations: Array<Location>,
  sfRegister_submitForm: Function
}
