import { useEffect, useState } from 'react';
import axios from 'axios';
import { Label } from '@/Components/ui/label';
import { SearchableSelect } from '@/Components/ui/searchable-select';

export default function RegionPicker({ value, onChange }) {
    const [provinces, setProvinces] = useState([]);
    const [cities, setCities] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [villages, setVillages] = useState([]);

    useEffect(() => {
        axios.get(route('regions.provinces')).then((r) => setProvinces(r.data));
    }, []);

    useEffect(() => {
        if (value.provinsi_code) {
            axios.get(route('regions.cities'), { params: { province: value.provinsi_code } })
                .then((r) => setCities(r.data));
        } else {
            setCities([]);
        }
    }, [value.provinsi_code]);

    useEffect(() => {
        if (value.kabupaten_code) {
            axios.get(route('regions.districts'), { params: { city: value.kabupaten_code } })
                .then((r) => setDistricts(r.data));
        } else {
            setDistricts([]);
        }
    }, [value.kabupaten_code]);

    useEffect(() => {
        if (value.kecamatan_code) {
            axios.get(route('regions.villages'), { params: { district: value.kecamatan_code } })
                .then((r) => setVillages(r.data));
        } else {
            setVillages([]);
        }
    }, [value.kecamatan_code]);

    function pickProvince(code) {
        const p = provinces.find((x) => x.code === code);
        onChange({
            ...value,
            provinsi_code: code || '',
            provinsi_nama: p?.name ?? '',
            kabupaten_code: '', kabupaten_nama: '',
            kecamatan_code: '', kecamatan_nama: '',
            desa_code: '', desa_nama: '',
        });
    }
    function pickCity(code) {
        const c = cities.find((x) => x.code === code);
        onChange({
            ...value,
            kabupaten_code: code || '',
            kabupaten_nama: c?.name ?? '',
            kecamatan_code: '', kecamatan_nama: '',
            desa_code: '', desa_nama: '',
        });
    }
    function pickDistrict(code) {
        const d = districts.find((x) => x.code === code);
        onChange({
            ...value,
            kecamatan_code: code || '',
            kecamatan_nama: d?.name ?? '',
            desa_code: '', desa_nama: '',
        });
    }
    function pickVillage(code) {
        const v = villages.find((x) => x.code === code);
        onChange({
            ...value,
            desa_code: code || '',
            desa_nama: v?.name ?? '',
        });
    }

    const provinceOptions = provinces.map((p) => ({ value: p.code, label: p.name }));
    const cityOptions = cities.map((c) => ({ value: c.code, label: c.name }));
    const districtOptions = districts.map((d) => ({ value: d.code, label: d.name }));
    const villageOptions = villages.map((v) => ({ value: v.code, label: v.name }));

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <Label>Provinsi</Label>
                <SearchableSelect
                    value={value.provinsi_code || ''}
                    onValueChange={pickProvince}
                    options={provinceOptions}
                    placeholder="Pilih provinsi"
                    className="mt-1.5"
                />
            </div>
            <div>
                <Label>Kabupaten / Kota</Label>
                <SearchableSelect
                    value={value.kabupaten_code || ''}
                    onValueChange={pickCity}
                    options={cityOptions}
                    placeholder="Pilih kabupaten"
                    disabled={!value.provinsi_code}
                    className="mt-1.5"
                />
            </div>
            <div>
                <Label>Kecamatan</Label>
                <SearchableSelect
                    value={value.kecamatan_code || ''}
                    onValueChange={pickDistrict}
                    options={districtOptions}
                    placeholder="Pilih kecamatan"
                    disabled={!value.kabupaten_code}
                    className="mt-1.5"
                />
            </div>
            <div>
                <Label>Desa / Kelurahan</Label>
                <SearchableSelect
                    value={value.desa_code || ''}
                    onValueChange={pickVillage}
                    options={villageOptions}
                    placeholder="Pilih desa"
                    disabled={!value.kecamatan_code}
                    className="mt-1.5"
                />
            </div>
        </div>
    );
}
