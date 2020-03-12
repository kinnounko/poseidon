import tensorflow as tf

from tensorflow import keras
from keras.models import Sequential
from keras.layers import Dense
from keras.wrappers.scikit_learn import KerasRegressor
import pandas as pd
import numpy as np
from matplotlib import pyplot as plt
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import MinMaxScaler


def filter_time(df, threshold):
    filter_df = df.loc[df.year > threshold].copy()
    return filter_df


def filter_cause(df):
    filter_df = df.loc[df.cause_code == "Earthquake"].copy()
    return filter_df


def remove_missing_cols(df, missing_threshold=0.6):
    missing_df = (pd.DataFrame(df.isna().sum() / len(df))
                  .reset_index()
                  .rename(columns={"index": "column", 0: "missing"}))
    missing_df.sort_values(by="missing", ascending=False)

    cols_to_sel = missing_df.loc[missing_df.missing <
                                 missing_threshold, "column"].copy().values.tolist()

    return df[cols_to_sel].copy()


def split_dataset(x, y, ratio=0.7):
    """Split the dataset into two parts - train and test
    Args:
        x: Dataset X
        y: Dataset y
        ratio: Ratio of split. Must be value between 0 and 1 
    Return:
        Array: [X_train, y_train, X_test, y_test]
    """
    cut = int(ratio * len(x))
    return [x[:cut], y[:cut], x[cut:], y[cut:]]


def prepare_dataset(df):
    # Decide on target
    # df = df.dropna()
    # df.fillna(df.mean(skipna=True), inplace=True)
    target = ["maximum_water_height"]

    cols_to_predict = ["latitude",
                       "longitude",
                       "primary_magnitude",
                       ]

    missing_vals_conds = ((df.primary_magnitude.isna()) &
                          (df.latitude.isna()) &
                          (df.longitude.isna()))

    no_na_df = df.loc[~missing_vals_conds].copy()
    no_na_df = no_na_df.loc[~no_na_df.maximum_water_height.isna()].copy()

    X = no_na_df[cols_to_predict].copy()
    y = no_na_df[target].copy()

    """     scaler_x = MinMaxScaler()
    scaler_y = MinMaxScaler()

    print(scaler_x.fit(X))
    xscale = scaler_x.transform(X)
    print(scaler_y.fit(y))
    yscale = scaler_y.transform(y)
    """

    x_train, x_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42)

    
    # Impute missing values
    for col in x_train.columns:
        x_train[col].fillna(x_train[col].mean(skipna=True), inplace=True)
    for col in x_test.columns:
        x_test[col].fillna(x_test[col].mean(skipna=True), inplace=True)

    return [x_train, x_test, y_train, y_test]


def build_model(X_train, x_test, y_train, y_test, epochs):
    model = Sequential()
    model.add(Dense(4, input_dim=3, kernel_initializer='normal', activation='relu'))
    model.add(Dense(4, activation='relu'))
    model.add(Dense(1, activation='linear'))
    model.summary()
    model.compile(loss='mse', optimizer='adam', metrics=['mse', 'mae'])
    history = model.fit(X_train, y_train, epochs=epochs,
                        batch_size=50,  verbose=1, validation_split=0.2)
    print(history.history.keys())
    # Plotting loss
    plt.plot(history.history['loss'])
    plt.plot(history.history['val_loss'])
    plt.title('model loss')
    plt.ylabel('loss')
    plt.xlabel('epoch')
    plt.legend(['train', 'validation'], loc='upper left')
    plt.show()

    return [model.predict(x_test)]


if __name__ == "__main__":
    # Read data
    print("Read files")
    sources = pd.read_csv("data/sources.csv", sep='\t',
                          lineterminator='\r', encoding='latin1')
    waves = pd.read_csv("data/waves.csv", sep='\t',
                        lineterminator='\r', encoding='latin1')

    waves.columns = waves.columns.str.lower()
    sources.columns = sources.columns.str.lower()

    # First, we process all the information

    # Mapping different causes
    causes = {0: 'Unknown',
              1: 'Earthquake',
              2: 'Questionable Earthquake',
              3: 'Earthquake and Landslide',
              4: 'Volcano and Earthquake',
              5: 'Volcano, Earthquake, and Landslide',
              6: 'Volcano',
              7: 'Volcano and Landslide',
              8: 'Landslide',
              9: 'Meteorological',
              10: 'Explosion',
              11: 'Astronomical Tide'}

    sources['cause_code'] = sources['cause_code'].map(causes)

    filter_waves = filter_time(waves, threshold=1900)
    filter_sources = filter_time(sources, threshold=1900)

    earthquake_df = filter_cause(filter_sources)
    earthquake_df_sel = remove_missing_cols(earthquake_df, missing_threshold=0.6)
    # earthquake_df_sel_indonesia = earthquake_df_sel.loc[earthquake_df_sel.country == "INDONESIA"].copy()

    # Merge both sources
    merged_with_sources = (earthquake_df_sel.merge(filter_sources[["id", "cause_code"]],
                                                   how="left", on=["id"], suffixes=("_wav",  "_sou")))

    merged_with_sources["month"] = merged_with_sources["month"].apply(
        lambda x: str(x)[:-2])
    merged_with_sources["day"] = merged_with_sources["day"].apply(lambda x: str(x)[
                                                                  :-2])

    merged_with_sources["hour"] = merged_with_sources["hour"].apply(
        lambda x: str(x)[:-2])
    merged_with_sources["minute"] = merged_with_sources["minute"].apply(
        lambda x: str(x)[:-2])

    merged_with_sources["date"] = (merged_with_sources["year"].map(str) + "-"
                                   + merged_with_sources["month"]
                                   + "-" + merged_with_sources["day"])

    merged_with_sources["hour"] = (merged_with_sources["hour"] + ":"
                                   + merged_with_sources["minute"])

    # merged_with_sources["date"] = pd.to_datetime(merged_with_sources["date"])
    # merged_with_sources.sort_values(by="date", inplace=True)

    print("Prepare dataset")
    X_train, y_train, X_test, y_test = prepare_dataset(merged_with_sources)
    print("Training")
    pred = build_model(X_train, y_train, X_test, y_test, 1000)
    y_test['predictions'] = np.array(pred[0])
    y_test.to_csv("predictions.csv")
